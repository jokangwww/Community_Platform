<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\EventBoothPlace;
use App\Models\Event;
use App\Models\VendorBoothApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VendorBoothApplicationController extends Controller
{
    private function resolveBoothNames(array $validated): array
    {
        $boothCount = (int) ($validated['booth_count'] ?? 0);
        if ($boothCount > 0) {
            $booths = [];
            for ($i = 1; $i <= $boothCount; $i++) {
                $booths[] = 'Booth ' . $i;
            }
            return $booths;
        }

        $booths = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($validated['booth_names'] ?? '')) ?: [])));
        return array_values(array_unique($booths));
    }

    // Organizer booth setup page for this club's approved events.
    public function index(Request $request): View
    {
        $club = $request->user();
        $eventQ = trim((string) $request->query('event_q', ''));

        $events = Event::query()
            ->with('boothPlaces.booths')
            ->where('club_id', $club->id)
            ->where('approval_status', 'approved')
            ->when($eventQ !== '', fn ($query) => $query->where('name', 'like', '%' . $eventQ . '%'))
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        return view('club.vendor-booth-applications.index', [
            'events' => $events,
            'filters' => ['event_q' => $eventQ],
        ]);
    }

    // Organizer-stage vendor application review page with search/status filters.
    public function applications(Request $request): View
    {
        $club = $request->user();
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $applications = VendorBoothApplication::query()
            ->with(['event', 'vendor', 'selectedBooth.boothPlace'])
            ->whereHas('event', fn ($query) => $query->where('club_id', $club->id))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('vendor_name_snapshot', 'like', '%' . $q . '%')
                        ->orWhere('vendor_email_snapshot', 'like', '%' . $q . '%')
                        ->orWhereHas('event', fn ($eventQuery) => $eventQuery->where('name', 'like', '%' . $q . '%'));
                });
            })
            ->when($status !== '' && in_array($status, ['pending_organizer', 'pending_admin', 'approved', 'rejected_organizer', 'rejected_admin'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('club.vendor-booth-applications.applications', [
            'applications' => $applications,
            'filters' => ['q' => $q, 'status' => $status],
        ]);
    }

    // Organizer creates a booth place with image and multiple booths.
    public function storeBoothPlace(Request $request, Event $event): RedirectResponse
    {
        $club = $request->user();
        abort_unless((int) $event->club_id === (int) $club->id, 403);

        $validated = $request->validate([
            'place_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'place_image' => ['required', 'image', 'max:4096'],
            'booth_count' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'booth_names' => ['nullable', 'string', 'max:5000'],
        ]);

        $booths = $this->resolveBoothNames($validated);
        if ($booths === []) {
            return back()->withErrors(['booth_count' => 'Please set booth count or enter booth names.']);
        }

        // Replace mode: keep only one latest booth-place setup per event.
        $event->load('boothPlaces');
        foreach ($event->boothPlaces as $existingPlace) {
            if (! empty($existingPlace->image_path)) {
                Storage::disk('public')->delete($existingPlace->image_path);
            }
        }
        $event->boothPlaces()->delete();

        $imagePath = $request->file('place_image')->store('booth-places', 'public');

        $place = EventBoothPlace::create([
            'event_id' => $event->id,
            'name' => trim((string) $validated['place_name']),
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'image_path' => $imagePath,
        ]);

        foreach ($booths as $boothName) {
            $place->booths()->create([
                'name' => $boothName,
            ]);
        }

        return back()->with('status', 'Booth setup replaced for ' . $event->name . ' (' . count($booths) . ' booth(s)).');
    }

    // Organizer edits an existing booth place and can regenerate booth list.
    public function updateBoothPlace(Request $request, Event $event, EventBoothPlace $place): RedirectResponse
    {
        $club = $request->user();
        abort_unless((int) $event->club_id === (int) $club->id, 403);
        abort_unless((int) $place->event_id === (int) $event->id, 404);

        $validated = $request->validate([
            'place_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'place_image' => ['nullable', 'image', 'max:4096'],
            'booth_count' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'booth_names' => ['nullable', 'string', 'max:5000'],
        ]);

        $updateData = [
            'name' => trim((string) $validated['place_name']),
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ];

        if ($request->hasFile('place_image')) {
            if (! empty($place->image_path)) {
                Storage::disk('public')->delete($place->image_path);
            }
            $updateData['image_path'] = $request->file('place_image')->store('booth-places', 'public');
        }

        $place->update($updateData);

        $booths = $this->resolveBoothNames($validated);
        if ($booths !== []) {
            $place->booths()->delete();
            foreach ($booths as $boothName) {
                $place->booths()->create([
                    'name' => $boothName,
                ]);
            }
        }

        return back()->with('status', 'Booth place updated: ' . $place->name . '.');
    }

    // Organizer removes a booth place and all booths under it.
    public function destroyBoothPlace(Request $request, Event $event, EventBoothPlace $place): RedirectResponse
    {
        $club = $request->user();
        abort_unless((int) $event->club_id === (int) $club->id, 403);
        abort_unless((int) $place->event_id === (int) $event->id, 404);

        if (! empty($place->image_path)) {
            Storage::disk('public')->delete($place->image_path);
        }

        $placeName = $place->name;
        $place->delete();

        return back()->with('status', 'Booth place removed: ' . $placeName . '.');
    }

    // Organizer-stage approve/reject action before the application moves to admin final review.
    public function update(Request $request, VendorBoothApplication $application): RedirectResponse
    {
        $club = $request->user();
        abort_unless((int) ($application->event?->club_id) === (int) $club->id, 403);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        // Only pending organizer-stage applications can be reviewed here.
        if ($application->status !== 'pending_organizer') {
            return back()->withErrors(['vendor' => 'This application is no longer in organizer review stage.']);
        }

        // Approve at organizer stage -> forward to admin review queue.
        if ($validated['action'] === 'approve') {
            $application->update([
                'status' => 'pending_admin',
                'organizer_reviewed_by' => $club->id,
                'organizer_review_reason' => null,
                'organizer_reviewed_at' => now(),
            ]);

            return back()->with('status', 'Application approved at organizer stage and forwarded to admin.');
        }

        // Organizer rejection requires a reason and closes the application at stage 1.
        $reason = trim((string) ($validated['reason'] ?? ''));
        if ($reason === '') {
            return back()->withErrors(['reason' => 'Organizer rejection reason is required.']);
        }

        $application->update([
            'status' => 'rejected_organizer',
            'organizer_reviewed_by' => $club->id,
            'organizer_review_reason' => $reason,
            'organizer_reviewed_at' => now(),
        ]);

        return back()->with('status', 'Application rejected at organizer stage and closed.');
    }
}
