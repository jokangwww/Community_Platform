<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTicketSetting;
use App\Models\StudentCalendarEvent;
use App\Models\TicketPurchase;
use App\Models\BuddyParticipant;
use App\Services\PayPalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TicketController extends Controller
{
    private function earlyBirdState(Event $event, $student): array
    {
        $event->loadMissing('ticketSetting');
        $setting = $event->ticketSetting;
        if (! $setting || ! $setting->early_bird_enabled) {
            return ['active' => false, 'eligible' => false, 'discount_percent' => 0.0, 'message' => null];
        }

        $startAt = $setting->early_bird_start_at;
        $endAt = $setting->early_bird_end_at;
        if (! $startAt || ! $endAt || ! now()->betweenIncluded($startAt, $endAt)) {
            return ['active' => false, 'eligible' => false, 'discount_percent' => 0.0, 'message' => null];
        }

        $selectedFaculties = collect((array) $setting->early_bird_faculties)
            ->map(fn ($value) => mb_strtolower(trim((string) $value)))
            ->filter()
            ->values();
        $selectedYears = collect((array) $setting->early_bird_study_years)
            ->map(fn ($value) => mb_strtolower(trim((string) $value)))
            ->filter()
            ->values();
        $selectedRoles = collect((array) $setting->early_bird_roles)
            ->map(fn ($value) => mb_strtolower(trim((string) $value)))
            ->filter()
            ->values();

        $hasCriteria = $selectedFaculties->isNotEmpty() || $selectedYears->isNotEmpty() || $selectedRoles->isNotEmpty();
        if (! $hasCriteria) {
            return [
                'active' => true,
                'eligible' => true,
                'discount_percent' => (float) ($setting->early_bird_discount_percent ?? 0),
                'message' => null,
            ];
        }

        $facultyMatch = $selectedFaculties->isNotEmpty()
            && $selectedFaculties->contains($this->normalizedFaculty($student->faculty ?? null));
        $yearMatch = $selectedYears->isNotEmpty()
            && $selectedYears->contains(mb_strtolower(trim((string) ($student->study_year ?? ''))));
        $mentorMatch = $selectedRoles->contains('mentor')
            && BuddyParticipant::query()
                ->where('user_id', $student->id)
                ->where('role', 'mentor')
                ->where('status', 'active')
                ->exists();

        $eligible = $facultyMatch || $yearMatch || $mentorMatch;

        return [
            'active' => true,
            'eligible' => $eligible,
            'discount_percent' => (float) ($setting->early_bird_discount_percent ?? 0),
            'message' => $eligible ? null : 'Early bird access is restricted to selected student categories. Please wait until general ticket sales open.',
        ];
    }

    private function discountComposition(Event $event, int $quantity, $student): array
    {
        $event->loadMissing('ticketSetting');
        $setting = $event->ticketSetting;
        $bundles = $this->normalizedBundleDiscounts($setting);
        $bundleDiscount = $this->resolveDiscountPercent($quantity, $bundles);
        $earlyBird = $this->earlyBirdState($event, $student);

        if ($earlyBird['active'] && ! $earlyBird['eligible']) {
            return [
                'blocked' => true,
                'message' => $earlyBird['message'],
                'bundle_percent' => $bundleDiscount,
                'early_bird_percent' => (float) $earlyBird['discount_percent'],
                'total_percent' => $bundleDiscount,
                'early_bird_applied' => false,
            ];
        }

        $earlyBirdApplied = $earlyBird['active'] && $earlyBird['eligible'] && (float) $earlyBird['discount_percent'] > 0;
        $earlyBirdPercent = $earlyBirdApplied ? (float) $earlyBird['discount_percent'] : 0.0;
        $totalDiscount = min(100.0, $bundleDiscount + $earlyBirdPercent);

        return [
            'blocked' => false,
            'message' => null,
            'bundle_percent' => $bundleDiscount,
            'early_bird_percent' => $earlyBirdPercent,
            'total_percent' => $totalDiscount,
            'early_bird_applied' => $earlyBirdApplied,
            'early_bird_active' => (bool) $earlyBird['active'],
        ];
    }

    private function remainingTicketCapacity(Event $event): ?int
    {
        $limit = (int) ($event->participant_limit ?? 0);
        if ($limit <= 0) {
            return null;
        }

        $sold = TicketPurchase::query()
            ->where('event_id', $event->id)
            ->where('status', 'completed')
            ->count();

        return max($limit - $sold, 0);
    }

    private function ticketCapacityMessage(Event $event, int $incomingQuantity): ?string
    {
        $remaining = $this->remainingTicketCapacity($event);
        if ($remaining === null) {
            return null;
        }

        $incomingQuantity = max($incomingQuantity, 1);
        if ($remaining <= 0) {
            return 'Ticket limit reached for this event.';
        }
        if ($incomingQuantity > $remaining) {
            return 'Only ' . $remaining . ' ticket(s) left for this event.';
        }

        return null;
    }

    private function normalizedFaculty(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function facultyLimitForStudent(Event $event, $student): ?int
    {
        $event->loadMissing('facultyLimits');
        if ($event->facultyLimits->isEmpty()) {
            return null;
        }

        $studentFaculty = $this->normalizedFaculty($student->faculty ?? null);
        if ($studentFaculty === '') {
            return 0;
        }

        $matched = $event->facultyLimits->first(function ($row) use ($studentFaculty) {
            return $this->normalizedFaculty($row->faculty_name ?? null) === $studentFaculty;
        });

        return $matched ? (int) $matched->limit : 0;
    }

    private function facultyTicketCount(Event $event, $student): int
    {
        $studentFaculty = $this->normalizedFaculty($student->faculty ?? null);
        if ($studentFaculty === '') {
            return 0;
        }

        return TicketPurchase::query()
            ->join('users', 'users.id', '=', 'ticket_purchases.student_id')
            ->where('ticket_purchases.event_id', $event->id)
            ->where('ticket_purchases.status', 'completed')
            ->whereRaw('LOWER(TRIM(COALESCE(users.faculty, ""))) = ?', [$studentFaculty])
            ->count();
    }

    private function facultyTicketLimitMessage(Event $event, $student, int $incomingQuantity = 1): ?string
    {
        $limit = $this->facultyLimitForStudent($event, $student);
        if ($limit === null) {
            return null;
        }
        if ($limit === 0) {
            return 'Your faculty is not eligible for this event.';
        }

        $current = $this->facultyTicketCount($event, $student);
        if ($current + max($incomingQuantity, 1) > $limit) {
            return 'Ticket limit reached for your faculty.';
        }

        return null;
    }

    private function isCommitteeMember($student, Event $event): bool
    {
        return $event->committeeMembers()->where('users.id', $student->id)->exists();
    }

    // Calendar cleanup/sync helpers keep ticket-based event entries accurate after transfer or resale.
    private function cleanupSellerCalendarIfNoTicket($student, Event $event): void
    {
        $hasRemainingTickets = TicketPurchase::query()
            ->where('student_id', $student->id)
            ->where('event_id', $event->id)
            ->exists();

        if (! $hasRemainingTickets) {
            StudentCalendarEvent::query()
                ->where('student_id', $student->id)
                ->where('event_id', $event->id)
                ->where('source', 'ticket')
                ->delete();
        }
    }

    // Helper method: ensure ticket transfer allowed.
    private function ensureTicketTransferAllowed(TicketPurchase $ticket): ?string
    {
        if (($ticket->event?->status ?? 'in_progress') === 'ended') {
            return 'Cannot transfer/resell ticket for an ended event.';
        }
        if ($ticket->attended_at) {
            return 'Cannot transfer/resell a ticket that has already been used for attendance.';
        }

        return null;
    }

    // Helper method: sync calendar entry.
    private function syncCalendarEntry($student, Event $event): void
    {
        $event->loadMissing('subEvents.locationPoint');
        $eventDate = $event->subEvents->pluck('event_date')->filter()->sort()->first()
            ?? $event->start_date
            ?? $event->end_date;
        $firstSubEvent = $event->subEvents
            ->filter(fn ($subEvent) => !empty($subEvent->event_date))
            ->sortBy('event_date')
            ->first();

        StudentCalendarEvent::updateOrCreate(
            [
                'student_id' => $student->id,
                'event_id' => $event->id,
            ],
            [
                'event_name' => $event->name,
                'event_date' => $eventDate,
                'event_start_time' => $firstSubEvent?->start_time ?: null,
                'event_end_time' => $firstSubEvent?->end_time ?: null,
                'venue' => $firstSubEvent?->locationPoint?->name ?: ($event->venue ?: null),
                'source' => 'ticket',
            ]
        );
    }

    // Student ticket management page: own tickets and resale marketplace listing.
    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = (string) $request->query('tab', 'mine');
        if (! in_array($tab, ['mine', 'resell'], true)) {
            $tab = 'mine';
        }
        $search = trim((string) $request->query('q', ''));

        $myTickets = TicketPurchase::query()
            ->with(['event', 'student'])
            ->where('student_id', $user->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('ticket_number', 'like', '%' . $search . '%')
                        ->orWhereHas('event', fn ($eventQuery) => $eventQuery->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('created_at')
            ->get();

        $resellListings = TicketPurchase::query()
            ->with(['event', 'student'])
            ->where('is_resale_listed', true)
            ->where('status', 'completed')
            ->where('student_id', '!=', $user->id)
            ->whereHas('event', function ($query) {
                $query->where('approval_status', 'approved')
                    ->where('status', '!=', 'ended');
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('ticket_number', 'like', '%' . $search . '%')
                        ->orWhereHas('event', fn ($eventQuery) => $eventQuery->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('resale_listed_at')
            ->get();

        $resaleSales = DB::table('ticket_resale_transactions as tx')
            ->join('events', 'events.id', '=', 'tx.event_id')
            ->join('users as buyer', 'buyer.id', '=', 'tx.buyer_id')
            ->where('tx.seller_id', $user->id)
            ->orderByDesc('tx.purchased_at')
            ->select([
                'tx.id',
                'tx.ticket_purchase_id',
                'tx.ticket_number',
                'tx.amount',
                'tx.currency',
                'tx.purchased_at',
                'events.name as event_name',
                'buyer.name as buyer_name',
                'buyer.student_id as buyer_student_id',
            ])
            ->get();

        return view('user.tickets.index', [
            'myTickets' => $myTickets,
            'resellListings' => $resellListings,
            'resaleSales' => $resaleSales,
            'filters' => [
                'tab' => $tab,
                'q' => $search,
            ],
        ]);
    }

    // Pricing helpers for normal ticket checkout (bundle discount support).
    private function normalizedBundleDiscounts(?EventTicketSetting $setting): array
    {
        $raw = $setting?->bundle_discounts;
        if (! is_array($raw)) {
            return [];
        }

        $bundles = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $quantity = (int) ($row['quantity'] ?? 0);
            $discountPercent = round((float) ($row['discount_percent'] ?? 0), 2);
            if ($quantity < 2 || $quantity > 100) {
                continue;
            }
            if ($discountPercent < 0 || $discountPercent > 100) {
                continue;
            }
            $bundles[$quantity] = [
                'quantity' => $quantity,
                'discount_percent' => $discountPercent,
            ];
        }

        ksort($bundles);

        return array_values($bundles);
    }

    // Helper method: resolve discount percent.
    private function resolveDiscountPercent(int $quantity, array $bundles): float
    {
        foreach ($bundles as $bundle) {
            if ((int) ($bundle['quantity'] ?? 0) === $quantity) {
                return (float) ($bundle['discount_percent'] ?? 0);
            }
        }

        return 0.0;
    }

    // Helper method: calculate total.
    private function calculateTotal(float $unitPrice, int $quantity, float $discountPercent): float
    {
        $subtotal = $unitPrice * $quantity;
        $discountAmount = $subtotal * ($discountPercent / 100);

        return round(max($subtotal - $discountAmount, 0), 2);
    }

    // Standard ticket purchase flow (checkout page, create PayPal order, capture payment, success page).
    public function checkout(Request $request, Event $event): View
    {
        $user = $request->user();

        if (($event->registration_type ?? 'register') !== 'ticket') {
            abort(404);
        }
        if (($event->approval_status ?? 'approved') !== 'approved') {
            abort(404);
        }

        $event->load('ticketSetting');
        $setting = $event->ticketSetting;
        if (! $setting || $setting->price <= 0) {
            abort(404);
        }
        if (($event->status ?? 'in_progress') === 'ended') {
            abort(404);
        }

        $earlyBird = $this->earlyBirdState($event, $user);
        if ($earlyBird['active'] && ! $earlyBird['eligible']) {
            return redirect()
                ->route('user.event-posting')
                ->with('status', (string) ($earlyBird['message'] ?? 'Early bird access is not available for your account yet.'));
        }

        return view('user.tickets.checkout', [
            'event' => $event,
            'setting' => $setting,
            'bundleDiscounts' => $this->normalizedBundleDiscounts($setting),
            'paypalClientId' => config('services.paypal.client_id'),
            'remainingTickets' => $this->remainingTicketCapacity($event),
            'earlyBird' => $earlyBird,
        ]);
    }

    // Controller action: create order.
    public function createOrder(Request $request, Event $event, PayPalService $payPal): JsonResponse
    {
        $user = $request->user();

        if (($event->registration_type ?? 'register') !== 'ticket') {
            abort(404);
        }
        if (($event->approval_status ?? 'approved') !== 'approved') {
            return response()->json(['message' => 'Event not approved.'], 422);
        }
        if ($this->isCommitteeMember($user, $event)) {
            return response()->json(['message' => 'Committee members cannot register as participants for this event.'], 422);
        }
        if (($event->status ?? 'in_progress') === 'ended') {
            return response()->json(['message' => 'Event ended.'], 422);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $event->load('ticketSetting');
        $setting = $event->ticketSetting;
        if (! $setting || $setting->price <= 0) {
            return response()->json(['message' => 'Ticket price is not set.'], 422);
        }
        if (($event->status ?? 'in_progress') === 'ended') {
            return response()->json(['message' => 'Event ended.'], 422);
        }

        $quantity = (int) $validated['quantity'];
        $capacityMessage = $this->ticketCapacityMessage($event, $quantity);
        if ($capacityMessage !== null) {
            return response()->json(['message' => $capacityMessage], 422);
        }
        $facultyLimitMessage = $this->facultyTicketLimitMessage($event, $user, $quantity);
        if ($facultyLimitMessage !== null) {
            return response()->json(['message' => $facultyLimitMessage], 422);
        }
        $discountRule = $this->discountComposition($event, $quantity, $user);
        if ($discountRule['blocked']) {
            return response()->json(['message' => $discountRule['message'] ?? 'Early bird access is not available for your account yet.'], 422);
        }
        $discountPercent = (float) $discountRule['total_percent'];
        $bundleDiscountPercent = (float) $discountRule['bundle_percent'];
        $earlyBirdDiscountPercent = (float) $discountRule['early_bird_percent'];
        $earlyBirdApplied = (bool) $discountRule['early_bird_applied'];
        $totalAmount = $this->calculateTotal((float) $setting->price, $quantity, $discountPercent);
        $amount = number_format($totalAmount, 2, '.', '');
        $currency = strtoupper($setting->currency ?: 'MYR');

        try {
            $order = $payPal->createOrder(
                'event-' . $event->id,
                $amount,
                $currency,
                'Ticket x' . $quantity . ' for ' . $event->name
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $orderId = (string) ($order['id'] ?? '');
        if ($orderId !== '') {
            $sessionOrders = (array) $request->session()->get('paypal_ticket_orders', []);
            $sessionOrders[$orderId] = [
                'event_id' => (int) $event->id,
                'user_id' => (int) $user->id,
                'quantity' => $quantity,
                'discount_percent' => $discountPercent,
                'bundle_discount_percent' => $bundleDiscountPercent,
                'early_bird_discount_percent' => $earlyBirdDiscountPercent,
                'early_bird_applied' => $earlyBirdApplied,
                'total_amount' => $totalAmount,
            ];
            $request->session()->put('paypal_ticket_orders', $sessionOrders);
        }

        return response()->json([
            'id' => $orderId !== '' ? $orderId : null,
        ]);
    }

    // Controller action: capture order.
    public function captureOrder(Request $request, Event $event, PayPalService $payPal): JsonResponse
    {
        $user = $request->user();

        $orderId = (string) $request->input('orderID');
        if ($orderId === '') {
            return response()->json(['message' => 'Order ID missing.'], 422);
        }
        if (($event->approval_status ?? 'approved') !== 'approved') {
            return response()->json(['message' => 'Event not approved.'], 422);
        }
        if ($this->isCommitteeMember($user, $event)) {
            return response()->json(['message' => 'Committee members cannot register as participants for this event.'], 422);
        }

        $sessionOrders = (array) $request->session()->get('paypal_ticket_orders', []);
        $sessionOrder = $sessionOrders[$orderId] ?? null;

        if (is_array($sessionOrder)
            && ((int) ($sessionOrder['event_id'] ?? 0) === (int) $event->id)
            && ((int) ($sessionOrder['user_id'] ?? 0) === (int) $user->id)) {
            $quantity = (int) ($sessionOrder['quantity'] ?? 1);
            $discountPercent = (float) ($sessionOrder['discount_percent'] ?? 0);
            $bundleDiscountPercent = (float) ($sessionOrder['bundle_discount_percent'] ?? 0);
            $earlyBirdDiscountPercent = (float) ($sessionOrder['early_bird_discount_percent'] ?? 0);
            $earlyBirdApplied = (bool) ($sessionOrder['early_bird_applied'] ?? false);
            $expectedTotalAmount = round((float) ($sessionOrder['total_amount'] ?? 0), 2);
        } else {
            $validated = $request->validate([
                'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            ]);
            $quantity = (int) $validated['quantity'];
            $event->load('ticketSetting');
            $setting = $event->ticketSetting;
            if (! $setting || $setting->price <= 0) {
                return response()->json(['message' => 'Ticket price is not set.'], 422);
            }
            $discountRule = $this->discountComposition($event, $quantity, $user);
            if ($discountRule['blocked']) {
                return response()->json(['message' => $discountRule['message'] ?? 'Early bird access is not available for your account yet.'], 422);
            }
            $discountPercent = (float) $discountRule['total_percent'];
            $bundleDiscountPercent = (float) $discountRule['bundle_percent'];
            $earlyBirdDiscountPercent = (float) $discountRule['early_bird_percent'];
            $earlyBirdApplied = (bool) $discountRule['early_bird_applied'];
            $expectedTotalAmount = $this->calculateTotal((float) $setting->price, $quantity, $discountPercent);
        }

        $event->load('ticketSetting');
        $setting = $event->ticketSetting;
        if (! $setting || $setting->price <= 0) {
            return response()->json(['message' => 'Ticket price is not set.'], 422);
        }
        if (($event->status ?? 'in_progress') === 'ended') {
            return response()->json(['message' => 'Event ended.'], 422);
        }

        $facultyLimitMessage = $this->facultyTicketLimitMessage($event, $user, $quantity);
        if ($facultyLimitMessage !== null) {
            return response()->json(['message' => $facultyLimitMessage], 422);
        }
        $capacityMessage = $this->ticketCapacityMessage($event, $quantity);
        if ($capacityMessage !== null) {
            return response()->json(['message' => $capacityMessage], 422);
        }

        try {
            $capture = $payPal->captureOrder($orderId);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $status = $capture['status'] ?? null;
        if ($status !== 'COMPLETED') {
            return response()->json(['message' => 'Payment not completed.'], 422);
        }

        $capturedAmount = (float) ($capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0);
        if (abs($capturedAmount - $expectedTotalAmount) > 0.01) {
            return response()->json(['message' => 'Captured amount mismatch.'], 422);
        }

        $purchases = DB::transaction(function () use ($event, $setting, $orderId, $capture, $user, $quantity, $discountPercent, $bundleDiscountPercent, $earlyBirdDiscountPercent, $earlyBirdApplied) {
            $setting = EventTicketSetting::where('id', $setting->id)->lockForUpdate()->first();
            $facultyLimitMessage = $this->facultyTicketLimitMessage($event, $user, $quantity);
            if ($facultyLimitMessage !== null) {
                return ['error' => $facultyLimitMessage];
            }
            $discountRule = $this->discountComposition($event, $quantity, $user);
            if ($discountRule['blocked']) {
                return ['error' => $discountRule['message'] ?? 'Early bird access is not available for your account yet.'];
            }
            $capacityMessage = $this->ticketCapacityMessage($event, $quantity);
            if ($capacityMessage !== null) {
                return ['error' => $capacityMessage];
            }

            $firstNumber = max($setting->last_number + 1, $setting->start_number);
            $lastNumber = $firstNumber + $quantity - 1;

            $setting->last_number = $lastNumber;
            $setting->save();

            $padding = (int) ($setting->number_padding ?? 0);
            $captureId = null;
            if (! empty($capture['purchase_units'][0]['payments']['captures'][0]['id'])) {
                $captureId = $capture['purchase_units'][0]['payments']['captures'][0]['id'];
            }

            $unitAmount = round((float) $setting->price * (1 - ($discountPercent / 100)), 2);
            $baseUnitAmount = round((float) $setting->price, 2);
            $currency = strtoupper($setting->currency ?: 'MYR');
            $created = [];
            for ($seq = $firstNumber; $seq <= $lastNumber; $seq++) {
                $numberText = $padding > 0
                    ? str_pad((string) $seq, $padding, '0', STR_PAD_LEFT)
                    : (string) $seq;
                $ticketNumber = ($setting->prefix ?? '') . $numberText . ($setting->suffix ?? '');

                $created[] = TicketPurchase::create([
                    'event_id' => $event->id,
                    'student_id' => $user->id,
                    'order_id' => $orderId,
                    'capture_id' => $captureId,
                    'amount' => $unitAmount,
                    'currency' => $currency,
                    'ticket_number' => $ticketNumber,
                    'ticket_number_seq' => $seq,
                    'status' => 'completed',
                    'early_bird_applied' => $earlyBirdApplied,
                    'early_bird_discount_percent' => $earlyBirdApplied ? $earlyBirdDiscountPercent : null,
                    'bundle_discount_percent' => $bundleDiscountPercent > 0 ? $bundleDiscountPercent : null,
                    'base_unit_amount' => $baseUnitAmount,
                ]);
            }

            return $created;
        });

        if (is_array($purchases) && isset($purchases['error'])) {
            return response()->json(['message' => (string) $purchases['error']], 422);
        }
        if (! $purchases || count($purchases) === 0) {
            return response()->json(['message' => 'Ticket limit reached.'], 422);
        }

        unset($sessionOrders[$orderId]);
        $request->session()->put('paypal_ticket_orders', $sessionOrders);

        $this->syncCalendarEntry($user, $event);

        return response()->json([
            'ticketId' => $purchases[0]->id,
            'ticketCount' => count($purchases),
        ]);
    }

    // Controller action: success.
    public function success(Request $request, Event $event, TicketPurchase $ticket): View
    {
        $user = $request->user();

        if ($ticket->student_id !== $user->id || $ticket->event_id !== $event->id) {
            abort(403);
        }

        $tickets = TicketPurchase::where('event_id', $event->id)
            ->where('student_id', $user->id)
            ->where('order_id', $ticket->order_id)
            ->orderBy('ticket_number_seq')
            ->get();

        return view('user.tickets.success', [
            'event' => $event,
            'ticket' => $ticket,
            'tickets' => $tickets,
        ]);
    }

    // Post-purchase ticket ownership actions: direct transfer and resale marketplace operations.
    public function transfer(Request $request, TicketPurchase $ticket): RedirectResponse
    {
        $user = $request->user();
        $ticket->load('event', 'student');

        if ((int) $ticket->student_id !== (int) $user->id) {
            abort(403);
        }

        if ($message = $this->ensureTicketTransferAllowed($ticket)) {
            return back()->withErrors(['ticket' => $message]);
        }

        $validated = $request->validate([
            'target_student_id' => ['required', 'string', 'max:255'],
        ]);

        $targetStudent = \App\Models\User::query()
            ->where('student_id', trim($validated['target_student_id']))
            ->where('role', 'student')
            ->first();

        if (! $targetStudent) {
            return back()->withErrors(['target_student_id' => 'Target student ID not found.']);
        }
        if ((int) $targetStudent->id === (int) $user->id) {
            return back()->withErrors(['target_student_id' => 'Cannot transfer ticket to yourself.']);
        }
        if ($ticket->event) {
            $facultyLimitMessage = $this->facultyTicketLimitMessage($ticket->event, $targetStudent, 1);
            if ($facultyLimitMessage !== null) {
                return back()->withErrors(['target_student_id' => $facultyLimitMessage]);
            }
        }

        DB::transaction(function () use ($ticket, $targetStudent, $user): void {
            $locked = TicketPurchase::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('event');
            if ($locked->event) {
                $facultyLimitMessage = $this->facultyTicketLimitMessage($locked->event, $targetStudent, 1);
                if ($facultyLimitMessage !== null) {
                    throw ValidationException::withMessages(['target_student_id' => $facultyLimitMessage]);
                }
            }

            $locked->student_id = $targetStudent->id;
            $locked->is_resale_listed = false;
            $locked->resale_price = null;
            $locked->resale_listed_at = null;
            $locked->last_transferred_at = now();
            $locked->save();

            $locked->loadMissing('event');
            if ($locked->event) {
                $this->syncCalendarEntry($targetStudent, $locked->event);
                $this->cleanupSellerCalendarIfNoTicket($user, $locked->event);
            }
        });

        return back()->with('status', 'Ticket transferred successfully.');
    }

    // Controller action: list for resale.
    public function listForResale(Request $request, TicketPurchase $ticket): RedirectResponse
    {
        $user = $request->user();
        $ticket->load('event');

        if ((int) $ticket->student_id !== (int) $user->id) {
            abort(403);
        }

        if ($message = $this->ensureTicketTransferAllowed($ticket)) {
            return back()->withErrors(['ticket' => $message]);
        }

        $validated = $request->validate([
            'resale_price' => ['required', 'numeric', 'min:0.01'],
        ]);

        $price = round((float) $validated['resale_price'], 2);
        $original = round((float) $ticket->amount, 2);

        if ($price > $original) {
            return back()->withErrors([
                'resale_price' => 'Resale price must be same as or lower than original ticket price (' . number_format($original, 2) . ').',
            ]);
        }

        $ticket->update([
            'is_resale_listed' => true,
            'resale_price' => $price,
            'resale_listed_at' => Carbon::now(),
        ]);

        return back()->with('status', 'Ticket listed for resale.');
    }

    // Controller action: cancel resale.
    public function cancelResale(Request $request, TicketPurchase $ticket): RedirectResponse
    {
        $user = $request->user();

        if ((int) $ticket->student_id !== (int) $user->id) {
            abort(403);
        }

        $ticket->update([
            'is_resale_listed' => false,
            'resale_price' => null,
            'resale_listed_at' => null,
        ]);

        return back()->with('status', 'Resale listing cancelled.');
    }

    // Controller action: resale checkout page.
    public function resaleCheckout(Request $request, TicketPurchase $ticket): View|RedirectResponse
    {
        $buyer = $request->user();
        $ticket->load('event', 'student');

        if ((int) $ticket->student_id === (int) $buyer->id) {
            return redirect()
                ->route('user.tickets.index', ['tab' => 'resell'])
                ->withErrors(['ticket' => 'You cannot buy your own ticket.']);
        }
        if (! $ticket->is_resale_listed || $ticket->resale_price === null) {
            return redirect()
                ->route('user.tickets.index', ['tab' => 'resell'])
                ->withErrors(['ticket' => 'This ticket is not available for resale.']);
        }
        if (($ticket->event?->status ?? 'in_progress') === 'ended') {
            return redirect()
                ->route('user.tickets.index', ['tab' => 'resell'])
                ->withErrors(['ticket' => 'This event has ended.']);
        }
        if ($ticket->attended_at) {
            return redirect()
                ->route('user.tickets.index', ['tab' => 'resell'])
                ->withErrors(['ticket' => 'This ticket has already been used.']);
        }
        if ($ticket->event) {
            $facultyLimitMessage = $this->facultyTicketLimitMessage($ticket->event, $buyer, 1);
            if ($facultyLimitMessage !== null) {
                return redirect()
                    ->route('user.tickets.index', ['tab' => 'resell'])
                    ->withErrors(['ticket' => $facultyLimitMessage]);
            }
        }

        return view('user.tickets.resale-checkout', [
            'ticket' => $ticket,
            'event' => $ticket->event,
            'seller' => $ticket->student,
            'paypalClientId' => config('services.paypal.client_id'),
        ]);
    }

    // Controller action: create PayPal order for resale purchase.
    public function createResaleOrder(Request $request, TicketPurchase $ticket, PayPalService $payPal): JsonResponse
    {
        $buyer = $request->user();
        $ticket->load('event');

        if ((int) $ticket->student_id === (int) $buyer->id) {
            return response()->json(['message' => 'You cannot buy your own ticket.'], 422);
        }
        if (! $ticket->is_resale_listed || $ticket->resale_price === null) {
            return response()->json(['message' => 'This ticket is not available for resale.'], 422);
        }
        if (($ticket->event?->status ?? 'in_progress') === 'ended') {
            return response()->json(['message' => 'This event has ended.'], 422);
        }
        if ($ticket->attended_at) {
            return response()->json(['message' => 'This ticket has already been used.'], 422);
        }
        if ($ticket->event) {
            $facultyLimitMessage = $this->facultyTicketLimitMessage($ticket->event, $buyer, 1);
            if ($facultyLimitMessage !== null) {
                return response()->json(['message' => $facultyLimitMessage], 422);
            }
        }

        $amount = number_format((float) $ticket->resale_price, 2, '.', '');
        $currency = strtoupper((string) ($ticket->currency ?: 'MYR'));

        try {
            $order = $payPal->createOrder(
                'resale-ticket-' . $ticket->id,
                $amount,
                $currency,
                'Resale ticket purchase for ' . ($ticket->event?->name ?: 'Event')
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $orderId = (string) ($order['id'] ?? '');
        if ($orderId !== '') {
            $sessionOrders = (array) $request->session()->get('paypal_resale_orders', []);
            $sessionOrders[$orderId] = [
                'ticket_id' => (int) $ticket->id,
                'event_id' => (int) ($ticket->event_id ?? 0),
                'buyer_id' => (int) $buyer->id,
                'seller_id' => (int) $ticket->student_id,
                'amount' => (float) $ticket->resale_price,
                'currency' => $currency,
            ];
            $request->session()->put('paypal_resale_orders', $sessionOrders);
        }

        return response()->json([
            'id' => $orderId !== '' ? $orderId : null,
        ]);
    }

    // Controller action: capture PayPal resale order and transfer ownership.
    public function captureResaleOrder(Request $request, TicketPurchase $ticket, PayPalService $payPal): JsonResponse
    {
        $buyer = $request->user();
        $orderId = (string) $request->input('orderID');
        if ($orderId === '') {
            return response()->json(['message' => 'Order ID missing.'], 422);
        }

        $sessionOrders = (array) $request->session()->get('paypal_resale_orders', []);
        $sessionOrder = $sessionOrders[$orderId] ?? null;
        if (! is_array($sessionOrder)
            || (int) ($sessionOrder['ticket_id'] ?? 0) !== (int) $ticket->id
            || (int) ($sessionOrder['buyer_id'] ?? 0) !== (int) $buyer->id) {
            return response()->json(['message' => 'Resale payment session expired. Please retry from checkout.'], 422);
        }

        try {
            $capture = $payPal->captureOrder($orderId);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $status = $capture['status'] ?? null;
        if ($status !== 'COMPLETED') {
            return response()->json(['message' => 'Payment not completed.'], 422);
        }

        $capturedAmount = (float) ($capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0);
        $expectedAmount = round((float) ($sessionOrder['amount'] ?? 0), 2);
        if (abs($capturedAmount - $expectedAmount) > 0.01) {
            return response()->json(['message' => 'Captured amount mismatch.'], 422);
        }

        $captureId = (string) ($capture['purchase_units'][0]['payments']['captures'][0]['id'] ?? '');

        DB::transaction(function () use ($ticket, $buyer, $orderId, $captureId, $expectedAmount): void {
            $locked = TicketPurchase::query()->with('event')->whereKey($ticket->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->student_id === (int) $buyer->id) {
                throw ValidationException::withMessages(['ticket' => 'You cannot buy your own ticket.']);
            }
            if (! $locked->is_resale_listed || $locked->resale_price === null) {
                throw ValidationException::withMessages(['ticket' => 'This ticket is not available for resale.']);
            }
            if (($locked->event?->status ?? 'in_progress') === 'ended') {
                throw ValidationException::withMessages(['ticket' => 'This event has ended.']);
            }
            if ($locked->attended_at) {
                throw ValidationException::withMessages(['ticket' => 'This ticket has already been used.']);
            }
            if ($locked->event) {
                $facultyLimitMessage = $this->facultyTicketLimitMessage($locked->event, $buyer, 1);
                if ($facultyLimitMessage !== null) {
                    throw ValidationException::withMessages(['ticket' => $facultyLimitMessage]);
                }
            }

            $sellerId = (int) $locked->student_id;

            DB::table('ticket_resale_transactions')->insert([
                'ticket_purchase_id' => $locked->id,
                'event_id' => $locked->event_id,
                'seller_id' => $sellerId,
                'buyer_id' => $buyer->id,
                'ticket_number' => $locked->ticket_number,
                'amount' => $expectedAmount,
                'currency' => strtoupper((string) ($locked->currency ?: 'MYR')),
                'order_id' => $orderId,
                'capture_id' => $captureId !== '' ? $captureId : null,
                'purchased_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $seller = \App\Models\User::query()->find($sellerId);

            $locked->student_id = $buyer->id;
            $locked->is_resale_listed = false;
            $locked->resale_price = null;
            $locked->resale_listed_at = null;
            $locked->last_transferred_at = now();
            $locked->save();

            if ($locked->event) {
                $this->syncCalendarEntry($buyer, $locked->event);
                if ($seller) {
                    $this->cleanupSellerCalendarIfNoTicket($seller, $locked->event);
                }
            }
        });

        unset($sessionOrders[$orderId]);
        $request->session()->put('paypal_resale_orders', $sessionOrders);

        return response()->json([
            'ok' => true,
            'redirect' => route('user.tickets.index', ['tab' => 'mine']),
        ]);
    }

    // Controller action: buy resale.
    public function buyResale(Request $request, TicketPurchase $ticket): RedirectResponse
    {
        return redirect()
            ->route('user.tickets.resell.checkout', $ticket)
            ->with('status', 'Please complete payment via PayPal to purchase this resale ticket.');
    }
}
