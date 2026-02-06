<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTicketSetting;
use App\Models\TicketPurchase;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function checkout(Event $event): View
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'student') {
            abort(403);
        }

        if (($event->registration_type ?? 'register') !== 'ticket') {
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
            'paypalClientId' => config('services.paypal.client_id'),
        ]);
    }

    public function createOrder(Request $request, Event $event, PayPalService $payPal): JsonResponse
    {
        $user = $request->user();
        if (! $user || $user->role !== 'student') {
            abort(403);
        }

        if (($event->registration_type ?? 'register') !== 'ticket') {
            abort(404);
        }
        if (($event->status ?? 'in_progress') === 'ended') {
            return response()->json(['message' => 'Event ended.'], 422);
        }

        $event->load('ticketSetting');
        $setting = $event->ticketSetting;
        if (! $setting || $setting->price <= 0) {
            return response()->json(['message' => 'Ticket price is not set.'], 422);
        }
        if (($event->status ?? 'in_progress') === 'ended') {
            return response()->json(['message' => 'Event ended.'], 422);
        }

        $amount = number_format((float) $setting->price, 2, '.', '');
        $currency = strtoupper($setting->currency ?: 'MYR');

        $order = $payPal->createOrder(
            'event-' . $event->id,
            $amount,
            $currency,
            'Ticket for ' . $event->name
        );

        return response()->json([
            'id' => $order['id'] ?? null,
        ]);
    }

    public function captureOrder(Request $request, Event $event, PayPalService $payPal): JsonResponse
    {
        $user = $request->user();
        if (! $user || $user->role !== 'student') {
            abort(403);
        }

        $orderId = (string) $request->input('orderID');
        if ($orderId === '') {
            return response()->json(['message' => 'Order ID missing.'], 422);
        }

        $event->load('ticketSetting');
        $setting = $event->ticketSetting;
        if (! $setting || $setting->price <= 0) {
            return response()->json(['message' => 'Ticket price is not set.'], 422);
        }

        $capture = $payPal->captureOrder($orderId);
        $status = $capture['status'] ?? null;
        if ($status !== 'COMPLETED') {
            return response()->json(['message' => 'Payment not completed.'], 422);
        }

        $purchase = DB::transaction(function () use ($event, $setting, $orderId, $capture, $user) {
            $setting = EventTicketSetting::where('id', $setting->id)->lockForUpdate()->first();

            $nextNumber = max($setting->last_number + 1, $setting->start_number);
            $limit = $event->participant_limit;
            if ($limit && $nextNumber > $limit) {
                return null;
            }

            $setting->last_number = $nextNumber;
            $setting->save();

            $padding = (int) ($setting->number_padding ?? 0);
            $numberText = $padding > 0
                ? str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT)
                : (string) $nextNumber;
            $ticketNumber = ($setting->prefix ?? '') . $numberText . ($setting->suffix ?? '');

            $captureId = null;
            if (! empty($capture['purchase_units'][0]['payments']['captures'][0]['id'])) {
                $captureId = $capture['purchase_units'][0]['payments']['captures'][0]['id'];
            }

            return TicketPurchase::create([
                'event_id' => $event->id,
                'student_id' => $user->id,
                'order_id' => $orderId,
                'capture_id' => $captureId,
                'amount' => $setting->price,
                'currency' => strtoupper($setting->currency ?: 'MYR'),
                'ticket_number' => $ticketNumber,
                'ticket_number_seq' => $nextNumber,
                'status' => 'completed',
            ]);
        });

        if (! $purchase) {
            return response()->json(['message' => 'Ticket limit reached.'], 422);
        }

        return response()->json([
            'ticketId' => $purchase->id,
        ]);
    }

    public function success(Event $event, TicketPurchase $ticket): View
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'student') {
            abort(403);
        }

        if ($ticket->student_id !== $user->id || $ticket->event_id !== $event->id) {
            abort(403);
        }

        return view('user.tickets.success', [
            'event' => $event,
            'ticket' => $ticket,
        ]);
    }
}
