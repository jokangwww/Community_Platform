<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenueBooking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VenueBookingController extends Controller
{
    // Club booking list page with search/status filters and completed-booking display logic.
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        // Completed filter includes explicitly completed bookings and approved bookings that already ended.
        $bookings = VenueBooking::query()
            ->with('venue')
            ->where('club_id', $request->user()->id)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('event_title', 'like', '%' . $q . '%')
                        ->orWhereHas('venue', function ($venueQuery) use ($q) {
                            $venueQuery->where('name', 'like', '%' . $q . '%')
                                ->orWhere('location', 'like', '%' . $q . '%');
                        });
                });
            })
            ->when($status !== '' && in_array($status, ['pending', 'approved', 'rejected', 'cancelled', 'completed'], true), function ($query) use ($status) {
                if ($status === 'completed') {
                    $query->where(function ($inner) {
                        $inner->where('status', 'completed')
                            ->orWhere(function ($approvedPast) {
                                $approvedPast->where('status', 'approved')
                                    ->where('end_at', '<', now());
                            });
                    });
                    return;
                }
                $query->where('status', $status);
            })
            ->orderByDesc('start_at')
            ->get();

        return view('club.venue-bookings.index', [
            'bookings' => $bookings,
            'filters' => ['q' => $q, 'status' => $status],
        ]);
    }

    // Venue booking application form loads currently active venues for selection.
    public function create(): View
    {
        return view('club.venue-bookings.create', [
            'venues' => Venue::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    // Submit a new venue booking request after validating schedule and checking real-time conflicts.
    public function store(Request $request): RedirectResponse
    {
        [$validated, $startAt, $endAt] = $this->validatedBookingInput($request);

        // Reject booking when selected venue is inactive or the timeslot overlaps an existing blocking booking.
        $venue = Venue::query()->findOrFail($validated['venue_id']);
        if (! $venue->is_active) {
            return back()->withErrors(['venue_id' => 'Selected venue is inactive.'])->withInput();
        }

        if ($this->hasConflict((int) $validated['venue_id'], $startAt, $endAt)) {
            return back()->withErrors(['venue_id' => 'Selected venue is not available for this timeslot.'])->withInput();
        }

        VenueBooking::create([
            'club_id' => $request->user()->id,
            'venue_id' => (int) $validated['venue_id'],
            'event_title' => trim($validated['event_title']),
            'event_details' => $validated['event_details'] ? trim($validated['event_details']) : null,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => 'pending',
        ]);

        return redirect()->route('club.venue-bookings.index')->with('status', 'Booking application submitted.');
    }

    // Edit form for a club-owned booking; keeps the current venue selectable even if now inactive.
    public function edit(Request $request, VenueBooking $venueBooking): View
    {
        $this->ensureClubOwnsBooking($request, $venueBooking);

        return view('club.venue-bookings.edit', [
            'booking' => $venueBooking->load('venue'),
            'venues' => Venue::query()->where('is_active', true)->orWhere('id', $venueBooking->venue_id)->orderBy('name')->get(),
        ]);
    }

    // Update a booking; approved bookings are returned to pending so admin can re-review changes.
    public function update(Request $request, VenueBooking $venueBooking): RedirectResponse
    {
        $this->ensureClubOwnsBooking($request, $venueBooking);

        if (in_array($venueBooking->status, ['cancelled', 'completed'], true)) {
            return back()->withErrors(['booking' => 'This booking can no longer be updated.']);
        }

        [$validated, $startAt, $endAt] = $this->validatedBookingInput($request);

        $venue = Venue::query()->findOrFail($validated['venue_id']);
        if (! $venue->is_active) {
            return back()->withErrors(['venue_id' => 'Selected venue is inactive.'])->withInput();
        }

        if ($this->hasConflict((int) $validated['venue_id'], $startAt, $endAt, $venueBooking->id)) {
            return back()->withErrors(['venue_id' => 'Selected venue is not available for this timeslot.'])->withInput();
        }

        // Editing an approved booking resets review fields and sends it back to pending approval.
        $nextStatus = $venueBooking->status === 'approved' ? 'pending' : $venueBooking->status;

        $venueBooking->update([
            'venue_id' => (int) $validated['venue_id'],
            'event_title' => trim($validated['event_title']),
            'event_details' => $validated['event_details'] ? trim($validated['event_details']) : null,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => $nextStatus,
            'admin_review_reason' => null,
            'admin_reviewed_by' => null,
            'admin_reviewed_at' => null,
        ]);

        $message = $nextStatus === 'pending'
            ? 'Booking updated and sent for approval again.'
            : 'Booking updated.';

        return redirect()->route('club.venue-bookings.index')->with('status', $message);
    }

    // Cancel a club-owned booking (soft close via status change, not row deletion).
    public function destroy(Request $request, VenueBooking $venueBooking): RedirectResponse
    {
        $this->ensureClubOwnsBooking($request, $venueBooking);

        if (in_array($venueBooking->status, ['cancelled', 'completed'], true)) {
            return back()->withErrors(['booking' => 'Booking is already closed.']);
        }

        $venueBooking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('status', 'Booking cancelled.');
    }

    // AJAX endpoint to check which active venues are free for a selected date/time range.
    public function availability(Request $request): JsonResponse
    {
        $request->validate([
            'booking_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ]);

        $startAt = Carbon::createFromFormat('Y-m-d H:i', $request->string('booking_date') . ' ' . $request->string('start_time'));
        $endAt = Carbon::createFromFormat('Y-m-d H:i', $request->string('booking_date') . ' ' . $request->string('end_time'));

        if ($endAt->lessThanOrEqualTo($startAt)) {
            return response()->json([
                'ok' => false,
                'message' => 'End time must be later than start time.',
                'available' => [],
            ], 422);
        }

        // Return a lightweight venue list for the frontend availability checker UI.
        $availableVenues = $this->availableVenuesForRange($startAt, $endAt)
            ->map(fn (Venue $venue) => [
                'id' => $venue->id,
                'name' => $venue->name,
                'location' => $venue->location,
            ])
            ->values();

        return response()->json([
            'ok' => true,
            'available' => $availableVenues,
            'message' => $availableVenues->isEmpty()
                ? 'No venue is available for the selected timeslot.'
                : $availableVenues->count() . ' venue(s) available.',
        ]);
    }

    // Shared ownership guard for edit/update/cancel booking actions.
    private function ensureClubOwnsBooking(Request $request, VenueBooking $venueBooking): void
    {
        abort_unless((int) $venueBooking->club_id === (int) $request->user()->id, 403);
    }

    // Validate booking form fields and convert the submitted date/time into Carbon timestamps.
    private function validatedBookingInput(Request $request): array
    {
        $validated = $request->validate([
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'event_title' => ['required', 'string', 'max:255'],
            'event_details' => ['nullable', 'string', 'max:2000'],
            'booking_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ]);

        $startAt = Carbon::createFromFormat('Y-m-d H:i', $validated['booking_date'] . ' ' . $validated['start_time']);
        $endAt = Carbon::createFromFormat('Y-m-d H:i', $validated['booking_date'] . ' ' . $validated['end_time']);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be later than start time.',
            ]);
        }

        return [$validated, $startAt, $endAt];
    }

    // Conflict check against blocking bookings (pending/approved depending on model scope) for the same venue.
    private function hasConflict(int $venueId, Carbon $startAt, Carbon $endAt, ?int $ignoreId = null): bool
    {
        return VenueBooking::query()
            ->where('venue_id', $venueId)
            ->blocking()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->overlappingRange($startAt, $endAt)
            ->exists();
    }

    // Return active venues that are not blocked by overlapping bookings in the requested timeslot.
    private function availableVenuesForRange(Carbon $startAt, Carbon $endAt): Collection
    {
        $blockedVenueIds = VenueBooking::query()
            ->blocking()
            ->overlappingRange($startAt, $endAt)
            ->pluck('venue_id');

        return Venue::query()
            ->where('is_active', true)
            ->when($blockedVenueIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $blockedVenueIds))
            ->orderBy('name')
            ->get(['id', 'name', 'location']);
    }
}
