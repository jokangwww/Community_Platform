<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenueBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VenueBookingApprovalController extends Controller
{
    // Load the main page listing and apply request filters if provided.
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $venueId = (string) $request->query('venue_id', '');

        $bookings = VenueBooking::query()
            ->with(['venue', 'club', 'reviewer'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('event_title', 'like', '%' . $q . '%')
                        ->orWhereHas('club', function ($clubQuery) use ($q) {
                            $clubQuery->where('name', 'like', '%' . $q . '%')
                                ->orWhere('display_name', 'like', '%' . $q . '%');
                        })
                        ->orWhereHas('venue', function ($venueQuery) use ($q) {
                            $venueQuery->where('name', 'like', '%' . $q . '%')
                                ->orWhere('location', 'like', '%' . $q . '%');
                        });
                });
            })
            ->when($venueId !== '' && ctype_digit($venueId), fn ($query) => $query->where('venue_id', (int) $venueId))
            ->when($status !== '', function ($query) use ($status) {
                if ($status === 'completed') {
                    $query->where('status', 'approved')->where('end_at', '<', now());
                    return;
                }
                if (in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
                    $query->where('status', $status);
                }
            })
            ->orderBy('start_at')
            ->get();

        return view('admin.venue-bookings.index', [
            'bookings' => $bookings,
            'venues' => Venue::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'q' => $q,
                'status' => $status,
                'venue_id' => $venueId,
            ],
        ]);
    }

    // Validate the request and update the existing record.
    public function update(Request $request, VenueBooking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject,completed'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = $validated['action'];
        $reason = isset($validated['reason']) ? trim($validated['reason']) : null;

        if ($action === 'reject' && $reason === '') {
            return back()->withErrors(['reason' => 'Rejection reason is required.']);
        }

        if ($action === 'approve') {
            if ($booking->status === 'cancelled') {
                return back()->withErrors(['booking' => 'Cancelled booking cannot be approved.']);
            }

            $conflict = VenueBooking::query()
                ->where('id', '!=', $booking->id)
                ->where('venue_id', $booking->venue_id)
                ->where('status', 'approved')
                ->overlappingRange($booking->start_at, $booking->end_at)
                ->exists();

            if ($conflict) {
                return back()->withErrors([
                    'booking' => 'Cannot approve because venue is already booked for this timeslot.',
                ]);
            }

            $booking->update([
                'status' => 'approved',
                'admin_review_reason' => null,
                'admin_reviewed_by' => $request->user()?->id,
                'admin_reviewed_at' => now(),
            ]);

            return back()->with('status', 'Booking approved.');
        }

        if ($action === 'reject') {
            $booking->update([
                'status' => 'rejected',
                'admin_review_reason' => $reason,
                'admin_reviewed_by' => $request->user()?->id,
                'admin_reviewed_at' => now(),
            ]);

            return back()->with('status', 'Booking rejected.');
        }

        $booking->update([
            'status' => 'completed',
            'admin_reviewed_by' => $request->user()?->id,
            'admin_reviewed_at' => now(),
        ]);

        return back()->with('status', 'Booking marked as completed.');
    }
}

