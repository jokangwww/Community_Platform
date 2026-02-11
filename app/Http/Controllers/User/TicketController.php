<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTicketSetting;
use App\Models\StudentCalendarEvent;
use App\Models\TicketPurchase;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TicketController extends Controller
{
    private function syncCalendarEntry($student, Event $event): void
    {
        $event->loadMissing('subEvents');
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
                'venue' => $event->venue ?: null,
                'source' => 'ticket',
            ]
        );
    }

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

    private function resolveDiscountPercent(int $quantity, array $bundles): float
    {
        foreach ($bundles as $bundle) {
            if ((int) ($bundle['quantity'] ?? 0) === $quantity) {
                return (float) ($bundle['discount_percent'] ?? 0);
            }
        }

        return 0.0;
    }

    private function calculateTotal(float $unitPrice, int $quantity, float $discountPercent): float
    {
        $subtotal = $unitPrice * $quantity;
        $discountAmount = $subtotal * ($discountPercent / 100);

        return round(max($subtotal - $discountAmount, 0), 2);
    }

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

    public function createOrder(Request $request, Event $event, PayPalService $payPal): JsonResponse
    {
        $user = $request->user();

        if (($event->registration_type ?? 'register') !== 'ticket') {
            abort(404);
        }
        if (($event->approval_status ?? 'approved') !== 'approved') {
            return response()->json(['message' => 'Event not approved.'], 422);
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
}
