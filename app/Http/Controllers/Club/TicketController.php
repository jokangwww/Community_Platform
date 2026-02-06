<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTicketSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            abort(403);
        }

        $events = Event::where('club_id', $user->id)
            ->where('registration_type', 'ticket')
            ->where('status', '!=', 'ended')
            ->with('ticketSetting')
            ->latest()
            ->get();

        return view('club.tickets.index', [
            'events' => $events,
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            abort(403);
        }

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

        $minLast = $setting->start_number - 1;
        $setting->last_number = max($setting->last_number ?? -1, $minLast);

        $setting->save();

        return back()->with('status', 'Ticket settings saved.');
    }
}
