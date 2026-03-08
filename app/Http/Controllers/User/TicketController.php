<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTicketSetting;
use App\Models\StudentCalendarEvent;
use App\Models\TicketPurchase;
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

        return view('user.tickets.index', [
            'myTickets' => $myTickets,
            'resellListings' => $resellListings,
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

        return view('user.tickets.checkout', [
            'event' => $event,
            'setting' => $setting,
            'bundleDiscounts' => $this->normalizedBundleDiscounts($setting),
            'paypalClientId' => config('services.paypal.client_id'),
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
        $bundles = $this->normalizedBundleDiscounts($setting);
        $discountPercent = $this->resolveDiscountPercent($quantity, $bundles);
        $totalAmount = $this->calculateTotal((float) $setting->price, $quantity, $discountPercent);
        $amount = number_format($totalAmount, 2, '.', '');
        $currency = strtoupper($setting->currency ?: 'MYR');

        $order = $payPal->createOrder(
            'event-' . $event->id,
            $amount,
            $currency,
            'Ticket x' . $quantity . ' for ' . $event->name
        );

        return response()->json([
            'id' => $order['id'] ?? null,
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
        $bundles = $this->normalizedBundleDiscounts($setting);
        $discountPercent = $this->resolveDiscountPercent($quantity, $bundles);
        $totalAmount = $this->calculateTotal((float) $setting->price, $quantity, $discountPercent);

        $capture = $payPal->captureOrder($orderId);
        $status = $capture['status'] ?? null;
        if ($status !== 'COMPLETED') {
            return response()->json(['message' => 'Payment not completed.'], 422);
        }

        $capturedAmount = (float) ($capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0);
        if (abs($capturedAmount - $totalAmount) > 0.01) {
            return response()->json(['message' => 'Captured amount mismatch.'], 422);
        }

        $purchases = DB::transaction(function () use ($event, $setting, $orderId, $capture, $user, $quantity, $discountPercent) {
            $setting = EventTicketSetting::where('id', $setting->id)->lockForUpdate()->first();

            $firstNumber = max($setting->last_number + 1, $setting->start_number);
            $lastNumber = $firstNumber + $quantity - 1;
            $limit = $event->participant_limit;
            if ($limit && $lastNumber > $limit) {
                return null;
            }

            $setting->last_number = $lastNumber;
            $setting->save();

            $padding = (int) ($setting->number_padding ?? 0);
            $captureId = null;
            if (! empty($capture['purchase_units'][0]['payments']['captures'][0]['id'])) {
                $captureId = $capture['purchase_units'][0]['payments']['captures'][0]['id'];
            }

            $unitAmount = round((float) $setting->price * (1 - ($discountPercent / 100)), 2);
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
                ]);
            }

            return $created;
        });

        if (! $purchases || count($purchases) === 0) {
            return response()->json(['message' => 'Ticket limit reached.'], 422);
        }
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

        DB::transaction(function () use ($ticket, $targetStudent, $user): void {
            $locked = TicketPurchase::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
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

    // Controller action: buy resale.
    public function buyResale(Request $request, TicketPurchase $ticket): RedirectResponse
    {
        $buyer = $request->user();

        DB::transaction(function () use ($ticket, $buyer): void {
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

            $originalAmount = round((float) $locked->amount, 2);
            $resalePrice = round((float) $locked->resale_price, 2);
            if ($resalePrice > $originalAmount) {
                throw ValidationException::withMessages(['ticket' => 'Invalid resale price. It exceeds original ticket price.']);
            }

            $seller = \App\Models\User::query()->find($locked->student_id);

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

        return back()->with('status', 'Resale ticket purchased successfully. Ticket ownership transferred to your account.');
    }
}
