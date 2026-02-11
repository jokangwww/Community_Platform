<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTicketSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    private function normalizeBundleDiscounts(array $quantities, array $percents): array
    {
        $bundles = [];
        $max = max(count($quantities), count($percents));

        for ($i = 0; $i < $max; $i++) {
            $qtyRaw = $quantities[$i] ?? null;
            $percentRaw = $percents[$i] ?? null;

            if ($qtyRaw === null || $qtyRaw === '' || $percentRaw === null || $percentRaw === '') {
                continue;
            }

            $quantity = (int) $qtyRaw;
            $discountPercent = round((float) $percentRaw, 2);

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

    public function index(Request $request): View
    {
        $user = $request->user();

        $search = trim((string) $request->query('q', ''));

        $events = Event::where('club_id', $user->id)
            ->where('registration_type', 'ticket')
            ->where('status', '!=', 'ended')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%');

                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->with('ticketSetting')
            ->latest()
            ->get();

        return view('club.tickets.index', [
            'events' => $events,
            'search' => $search,
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $user = $request->user();

        if ($event->club_id !== $user->id) {
            abort(403);
        }

        if (($event->registration_type ?? 'register') !== 'ticket') {
            return back()->with('status', 'This event is not set to ticket required.');
        }

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'prefix' => ['nullable', 'string', 'max:20'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'start_number' => ['required', 'integer', 'min:0', 'max:1000000'],
            'number_padding' => ['nullable', 'integer', 'min:0', 'max:6'],
            'bundle_quantity' => ['nullable', 'array'],
            'bundle_quantity.*' => ['nullable', 'integer', 'min:2', 'max:100'],
            'bundle_discount_percent' => ['nullable', 'array'],
            'bundle_discount_percent.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $setting = EventTicketSetting::firstOrNew([
            'event_id' => $event->id,
        ]);

        $setting->price = (float) $validated['price'];
        $setting->currency = strtoupper($validated['currency'] ?? ($setting->currency ?: 'MYR'));
        $setting->prefix = $validated['prefix'] ?: null;
        $setting->suffix = $validated['suffix'] ?: null;
        $setting->start_number = (int) $validated['start_number'];
        $setting->number_padding = (int) ($validated['number_padding'] ?? 0);
        $setting->bundle_discounts = $this->normalizeBundleDiscounts(
            $validated['bundle_quantity'] ?? [],
            $validated['bundle_discount_percent'] ?? []
        ) ?: null;

        $minLast = $setting->start_number - 1;
        $setting->last_number = max($setting->last_number ?? -1, $minLast);

        $setting->save();

        return back()->with('status', 'Ticket settings saved.');
    }
}
