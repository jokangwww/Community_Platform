<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use App\Models\Posting;
use App\Models\StudentCalendarEvent;
use App\Models\User;

class PostingController extends Controller
{
    private function syncCalendarEntry(User $student, Posting $posting): void
    {
        $posting->loadMissing(['event.subEvents']);
        $event = $posting->event;
        if (! $event) {
            return;
        }

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
                'source' => 'register',
            ]
        );
    }

    private function authenticatedStudent(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    private function favoriteIds(User $user): array
    {
        return $user->favoritePostings()
            ->pluck('postings.id')
            ->all();
    }

    private function registeredPostingIds(User $user): array
    {
        return EventRegistration::where('student_id', $user->id)
            ->pluck('posting_id')
            ->all();
    }

    private function eventRegistrationCounts(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        return EventRegistration::query()
            ->join('postings', 'event_registrations.posting_id', '=', 'postings.id')
            ->whereIn('postings.event_id', $eventIds)
            ->groupBy('postings.event_id')
            ->selectRaw('postings.event_id, COUNT(*) as total')
            ->pluck('total', 'postings.event_id')
            ->all();
    }

    public function index()
    {
        $user = $this->authenticatedStudent();

        $postings = Posting::with(['event.ticketSetting', 'images'])
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended')
                    ->where('approval_status', 'approved');
            })
            ->latest()
            ->get();

        return view('user.event-posting', [
            'postings' => $postings,
            'activeTab' => 'all',
            'favoriteIds' => $this->favoriteIds($user),
            'registeredIds' => $this->registeredPostingIds($user),
            'canRegister' => true,
            'eventRegistrationCounts' => $this->eventRegistrationCounts($postings->pluck('event_id')->filter()->unique()->all()),
        ]);
    }

    public function favorites()
    {
        $user = $this->authenticatedStudent();

        $postings = $user->favoritePostings()
            ->with(['event.ticketSetting', 'images'])
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended')
                    ->where('approval_status', 'approved');
            })
            ->latest('postings.created_at')
            ->get();

        return view('user.event-posting', [
            'postings' => $postings,
            'activeTab' => 'favorites',
            'favoriteIds' => $this->favoriteIds($user),
            'registeredIds' => $this->registeredPostingIds($user),
            'canRegister' => true,
            'eventRegistrationCounts' => $this->eventRegistrationCounts($postings->pluck('event_id')->filter()->unique()->all()),
        ]);
    }

    public function show(Posting $posting)
    {
        $user = $this->authenticatedStudent();

        $posting->load(['event.ticketSetting', 'images']);
        if (($posting->event?->status ?? 'in_progress') === 'ended'
            || ($posting->event?->approval_status ?? 'approved') !== 'approved') {
            abort(404);
        }

        return view('user.event-posting-show', [
            'posting' => $posting,
            'favoriteIds' => $this->favoriteIds($user),
            'registeredIds' => $this->registeredPostingIds($user),
            'canRegister' => true,
            'eventRegistrationCounts' => $this->eventRegistrationCounts($posting->event_id ? [$posting->event_id] : []),
        ]);
    }

    public function register(Posting $posting)
    {
        $user = $this->authenticatedStudent();

        if (($posting->status ?? 'open') !== 'open') {
            return redirect()
                ->back()
                ->with('status', 'Registration is closed for this event.');
        }

        $posting->loadMissing('event');
        if (($posting->event?->status ?? 'in_progress') === 'ended') {
            return redirect()
                ->back()
                ->with('status', 'This event has ended.');
        }
        if (($posting->event?->approval_status ?? 'approved') !== 'approved') {
            return redirect()
                ->back()
                ->with('status', 'This event has not been approved yet.');
        }
        $limit = $posting->event?->participant_limit;
        if (($posting->event?->registration_type ?? 'register') === 'ticket') {
            return redirect()
                ->back()
                ->with('status', 'This event requires a ticket purchase.');
        }
        if ($limit) {
            $currentCount = EventRegistration::whereHas('posting', function ($query) use ($posting) {
                $query->where('event_id', $posting->event_id);
            })->count();
            if ($currentCount >= $limit) {
                return redirect()
                    ->back()
                    ->with('status', 'Registration limit reached for this event.');
            }
        }

        EventRegistration::firstOrCreate([
            'posting_id' => $posting->id,
            'student_id' => $user->id,
        ]);
        $this->syncCalendarEntry($user, $posting);

        return redirect()
            ->back()
            ->with('status', 'Registration submitted.');
    }

    public function toggleFavorite(Posting $posting)
    {
        $user = $this->authenticatedStudent();

        $user->favoritePostings()->toggle($posting->id);

        return redirect()
            ->back();
    }
}
