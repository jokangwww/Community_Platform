<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenueBooking;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $operational = (string) $request->query('operational', '');

        $venues = Venue::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%' . $q . '%')
                        ->orWhere('location', 'like', '%' . $q . '%');
                });
            })
            ->when(in_array($operational, ['active', 'inactive'], true), function ($query) use ($operational) {
                $query->where('is_active', $operational === 'active');
            })
            ->orderBy('name')
            ->get();

        $now = now();
        $venueIds = $venues->pluck('id');

        $currentApprovedCounts = VenueBooking::query()
            ->selectRaw('venue_id, COUNT(*) as aggregate')
            ->whereIn('venue_id', $venueIds)
            ->where('status', 'approved')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->groupBy('venue_id')
            ->pluck('aggregate', 'venue_id');

        $activeUpcomingCounts = VenueBooking::query()
            ->selectRaw('venue_id, COUNT(*) as aggregate')
            ->whereIn('venue_id', $venueIds)
            ->whereIn('status', ['pending', 'approved'])
            ->where('end_at', '>=', $now)
            ->groupBy('venue_id')
            ->pluck('aggregate', 'venue_id');

        return view('admin.venues.index', [
            'venues' => $venues,
            'currentApprovedCounts' => $currentApprovedCounts,
            'activeUpcomingCounts' => $activeUpcomingCounts,
            'filters' => [
                'q' => $q,
                'operational' => $operational,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:venues,name'],
            'location' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Venue::create([
            'name' => trim($validated['name']),
            'location' => trim($validated['location']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'notes' => isset($validated['notes']) ? trim($validated['notes']) : null,
        ]);

        return back()->with('status', 'Venue created.');
    }

    public function update(Request $request, Venue $venue): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:venues,name,' . $venue->id],
            'location' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $venue->update([
            'name' => trim($validated['name']),
            'location' => trim($validated['location']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'notes' => isset($validated['notes']) ? trim($validated['notes']) : null,
        ]);

        return back()->with('status', 'Venue updated.');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        $hasLinkedActiveUpcomingBooking = $venue->bookings()
            ->whereIn('status', ['pending', 'approved'])
            ->where('end_at', '>=', Carbon::now())
            ->exists();

        if ($hasLinkedActiveUpcomingBooking) {
            return back()->withErrors([
                'venue' => 'Cannot delete venue because it has active or upcoming bookings.',
            ]);
        }

        $venue->delete();

        return back()->with('status', 'Venue deleted.');
    }
}

