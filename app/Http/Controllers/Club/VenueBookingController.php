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
    private function bookingIsUsingNow(VenueBooking $venueBooking): bool
    {
        return $venueBooking->status === 'approved'
            && $venueBooking->start_at
            && $venueBooking->end_at
            && $venueBooking->start_at->lessThanOrEqualTo(now())
            && $venueBooking->end_at->isFuture();
    }

    private function bookingIsLockedForOrganizer(VenueBooking $venueBooking): bool
    {
        return in_array($venueBooking->status, ['rejected', 'cancelled', 'completed'], true)
            || $this->bookingIsUsingNow($venueBooking);
    }

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
            ->when($status !== '' && in_array($status, ['pending', 'approved', 'using', 'rejected', 'cancelled', 'completed'], true), function ($query) use ($status) {
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
                if ($status === 'using') {
                    $query->where('status', 'approved')
                        ->where('start_at', '<=', now())
                        ->where('end_at', '>', now());
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
    public function edit(Request $request, VenueBooking $venueBooking): View|RedirectResponse
    {
        $this->ensureClubOwnsBooking($request, $venueBooking);
        if ($this->bookingIsLockedForOrganizer($venueBooking)) {
            return redirect()
                ->route('club.venue-bookings.index')
                ->withErrors(['booking' => 'Using, completed, rejected, or cancelled bookings cannot be edited. Please submit a new booking application.']);
        }

        return view('club.venue-bookings.edit', [
            'booking' => $venueBooking->load('venue'),
            'venues' => Venue::query()->where('is_active', true)->orWhere('id', $venueBooking->venue_id)->orderBy('name')->get(),
        ]);
    }

    // Update a booking; approved bookings are returned to pending so admin can re-review changes.
    public function update(Request $request, VenueBooking $venueBooking): RedirectResponse
    {
        $this->ensureClubOwnsBooking($request, $venueBooking);

        if ($this->bookingIsLockedForOrganizer($venueBooking)) {
            return redirect()
                ->route('club.venue-bookings.index')
                ->withErrors(['booking' => 'Using, completed, rejected, or cancelled bookings cannot be edited. Please submit a new booking application.']);
        }

        [$validated, $startAt, $endAt] = $this->validatedBookingInput($request);

        $venue = Venue::query()->findOrFail($validated['venue_id']);
        if (! $venue->is_active) {
            return back()->withErrors(['venue_id' => 'Selected venue is inactive.'])->withInput();
        }

        if ($this->hasConflict((int) $validated['venue_id'], $startAt, $endAt, $venueBooking->id)) {
            return back()->withErrors(['venue_id' => 'Selected venue is not available for this timeslot.'])->withInput();
        }

        // Any organizer edit requires admin re-review, so status is reset to pending.
        $nextStatus = 'pending';

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

        return redirect()
            ->route('club.venue-bookings.index')
            ->with('status', 'Booking updated and sent for approval again.');
    }

    // Cancel a club-owned booking (soft close via status change, not row deletion).
    public function destroy(Request $request, VenueBooking $venueBooking): RedirectResponse
    {
        $this->ensureClubOwnsBooking($request, $venueBooking);

        if ($this->bookingIsLockedForOrganizer($venueBooking)) {
            return back()->withErrors(['booking' => 'Using, completed, rejected, or cancelled bookings cannot be cancelled. Please submit a new booking application.']);
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
        $bookingDate = trim((string) $request->query('booking_date', ''));
        $startTime = trim((string) $request->query('start_time', ''));
        $endTime = trim((string) $request->query('end_time', ''));
        $venueIdRaw = $request->query('venue_id');
        $hasVenue = filled($venueIdRaw);
        $hasRange = $bookingDate !== '' && $startTime !== '' && $endTime !== '';

        if (! $hasVenue && ! $hasRange) {
            return response()->json([
                'ok' => false,
                'mode' => 'none',
                'message' => 'Select date/time first to find available venues, or select a venue first to see available dates.',
            ], 422);
        }

        if ($hasRange) {
            $validated = $request->validate([
                'booking_date' => ['required', 'date'],
                'start_time' => ['required', 'date_format:H:i'],
                'end_time' => ['required', 'date_format:H:i'],
                'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            ]);

            $startAt = Carbon::createFromFormat('Y-m-d H:i', $validated['booking_date'] . ' ' . $validated['start_time']);
            $endAt = Carbon::createFromFormat('Y-m-d H:i', $validated['booking_date'] . ' ' . $validated['end_time']);

            if ($endAt->lessThanOrEqualTo($startAt)) {
                return response()->json([
                    'ok' => false,
                    'mode' => 'timeslot',
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

            $selectedVenueAvailable = null;
            if (! empty($validated['venue_id'])) {
                $selectedVenueAvailable = $availableVenues->contains(fn (array $item) => (int) $item['id'] === (int) $validated['venue_id']);
            }

            return response()->json([
                'ok' => true,
                'mode' => 'timeslot',
                'available' => $availableVenues,
                'selected_venue_available' => $selectedVenueAvailable,
                'message' => $availableVenues->isEmpty()
                    ? 'No venue is available for the selected timeslot.'
                    : $availableVenues->count() . ' venue(s) available.',
            ]);
        }

        $validated = $request->validate([
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
        ]);

        $today = now()->startOfDay();
        $days = 14;
        $windowEnd = (clone $today)->addDays($days - 1)->endOfDay();

        $bookings = VenueBooking::query()
            ->blocking()
            ->where('venue_id', (int) $validated['venue_id'])
            ->where('start_at', '<=', $windowEnd)
            ->where('end_at', '>=', $today)
            ->orderBy('start_at')
            ->get(['start_at', 'end_at', 'event_title', 'status']);

        $bookingsByDate = $bookings->groupBy(fn (VenueBooking $booking) => $booking->start_at?->format('Y-m-d'));
        $dateSummaries = collect();
        for ($offset = 0; $offset < $days; $offset++) {
            $date = (clone $today)->addDays($offset)->format('Y-m-d');
            $items = ($bookingsByDate->get($date) ?? collect())
                ->map(fn (VenueBooking $booking) => [
                    'start_time' => $booking->start_at?->format('H:i'),
                    'end_time' => $booking->end_at?->format('H:i'),
                    'event_title' => $booking->event_title,
                    'status' => $booking->status,
                ])
                ->values();

            $dateSummaries->push([
                'date' => $date,
                'is_available' => $items->isEmpty(),
                'booked_slots' => $items,
            ]);
        }

        $availableDates = $dateSummaries
            ->where('is_available', true)
            ->pluck('date')
            ->values();

        return response()->json([
            'ok' => true,
            'mode' => 'venue',
            'available_dates' => $availableDates,
            'date_summaries' => $dateSummaries->values(),
            'message' => $availableDates->isEmpty()
                ? 'No fully free date found in the next 14 days for this venue.'
                : 'Showing available dates for the next 14 days.',
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
