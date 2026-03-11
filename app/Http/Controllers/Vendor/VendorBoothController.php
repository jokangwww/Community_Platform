<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\EventBooth;
use App\Models\Event;
use App\Models\VendorBoothApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorBoothController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $vendor = $request->user();

        $events = Event::query()
            ->with(['club', 'boothPlaces.booths'])
            ->where('approval_status', 'approved')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%' . $q . '%')
                        ->orWhere('venue', 'like', '%' . $q . '%')
                        ->orWhereHas('club', function ($clubQuery) use ($q) {
                            $clubQuery->where('name', 'like', '%' . $q . '%')
                                ->orWhere('display_name', 'like', '%' . $q . '%');
                        });
                });
            })
            ->orderByDesc('start_date')
            ->get();

        $myApplications = VendorBoothApplication::query()
            ->with(['event', 'selectedBooth.boothPlace'])
            ->where('vendor_id', $vendor->id)
            ->when($status !== '' && in_array($status, ['pending_organizer', 'pending_admin', 'approved', 'rejected_organizer', 'rejected_admin'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        $appliedByEvent = VendorBoothApplication::query()
            ->where('vendor_id', $vendor->id)
            ->pluck('status', 'event_id');

        $takenBoothIdsByEvent = [];
        foreach ($events as $event) {
            $takenBoothIdsByEvent[$event->id] = VendorBoothApplication::query()
                ->where('event_id', $event->id)
                ->where('status', 'approved')
                ->pluck('selected_event_booth_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return view('vendor.booth-applications.index', [
            'events' => $events,
            'myApplications' => $myApplications,
            'appliedByEvent' => $appliedByEvent,
            'takenBoothIdsByEvent' => $takenBoothIdsByEvent,
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        if (($event->approval_status ?? '') !== 'approved') {
            return back()->withErrors(['vendor' => 'Only approved events accept vendor applications.']);
        }

        $validated = $request->validate([
            'items_for_sale' => ['required', 'string', 'max:2000'],
            'selected_event_booth_id' => ['required', 'integer', 'exists:event_booths,id'],
        ]);

        $vendor = $request->user();
        $selectedBooth = EventBooth::query()
            ->with('boothPlace')
            ->find((int) $validated['selected_event_booth_id']);
        if (! $selectedBooth || (int) ($selectedBooth->boothPlace?->event_id) !== (int) $event->id) {
            return back()->withErrors(['selected_event_booth_id' => 'Selected booth is invalid for this event.']);
        }

        $currentApplication = VendorBoothApplication::query()
            ->where('vendor_id', $vendor->id)
            ->where('event_id', $event->id)
            ->first();

        if (($currentApplication->status ?? null) === 'approved') {
            return back()->withErrors([
                'vendor' => 'Your application for this event is already approved. Resubmission is not allowed.',
            ]);
        }

        $boothAlreadyTaken = VendorBoothApplication::query()
            ->where('event_id', $event->id)
            ->where('selected_event_booth_id', $selectedBooth->id)
            ->where('status', 'approved')
            ->when($currentApplication, fn ($query) => $query->where('id', '!=', $currentApplication->id))
            ->exists();

        if ($boothAlreadyTaken) {
            return back()->withErrors(['selected_event_booth_id' => 'This booth has already been taken. Please choose another booth.']);
        }

        VendorBoothApplication::updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'event_id' => $event->id,
            ],
            [
                'vendor_name_snapshot' => (string) ($vendor->name ?? ''),
                'vendor_email_snapshot' => (string) ($vendor->email ?? ''),
                'vendor_phone_snapshot' => (string) ($vendor->contact_information ?? ''),
                'items_for_sale' => trim($validated['items_for_sale']),
                'selected_booth_location' => trim(($selectedBooth->boothPlace?->name ? $selectedBooth->boothPlace->name . ' - ' : '') . $selectedBooth->name),
                'selected_event_booth_id' => $selectedBooth->id,
                'status' => 'pending_organizer',
                'organizer_reviewed_by' => null,
                'organizer_review_reason' => null,
                'organizer_reviewed_at' => null,
                'admin_reviewed_by' => null,
                'admin_review_reason' => null,
                'admin_reviewed_at' => null,
            ]
        );

        return back()->with('status', 'Vendor application submitted. Status: Pending organizer review.');
    }
}
