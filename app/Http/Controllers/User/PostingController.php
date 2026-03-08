<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Posting;
use App\Models\StudentCalendarEvent;
use App\Models\TicketPurchase;
use App\Models\User;
use App\Notifications\OverlappingScheduleAlertNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostingController extends Controller
{
    // Shared filters used by event posting list/favorites pages (keyword + lifecycle status).
    private function applySearchAndLifecycleFilters($query, Request $request): void
    {
        $keyword = trim((string) $request->query('q', ''));
        $lifecycle = (string) $request->query('lifecycle', 'all');

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('description', 'like', '%' . $keyword . '%')
                    ->orWhereHas('event', function ($eventQuery) use ($keyword) {
                        $eventQuery->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('club', function ($clubQuery) use ($keyword) {
                        $clubQuery->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('display_name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($lifecycle === 'current') {
            $query->where(function ($builder) {
                $builder->whereNull('outdated_at')
                    ->orWhere('outdated_at', '>', now());
            });
        } elseif ($lifecycle === 'outdated') {
            $query->whereNotNull('outdated_at')
                ->where('outdated_at', '<=', now());
        }
    }

    // Helper method: index filters.
    private function indexFilters(Request $request): array
    {
        $lifecycle = (string) $request->query('lifecycle', 'all');
        if (! in_array($lifecycle, ['all', 'current', 'outdated'], true)) {
            $lifecycle = 'all';
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'lifecycle' => $lifecycle,
        ];
    }

    // Calendar synchronization after student registration and overlap detection for sub-events.
    private function syncCalendarEntry(User $student, Posting $posting): ?StudentCalendarEvent
    {
        $posting->loadMissing(['event.subEvents.locationPoint']);
        $event = $posting->event;
        if (! $event) {
            return null;
        }

        $eventDate = $event->subEvents->pluck('event_date')->filter()->sort()->first()
            ?? $event->start_date
            ?? $event->end_date;
        $firstSubEvent = $event->subEvents
            ->filter(fn ($subEvent) => !empty($subEvent->event_date))
            ->sortBy('event_date')
            ->first();

        return StudentCalendarEvent::updateOrCreate(
            [
                'student_id' => $student->id,
                'event_id' => $event->id,
            ],
            [
                'event_name' => $event->name,
                'event_date' => $eventDate,
                'event_start_time' => $firstSubEvent?->start_time ?: null,
                'event_end_time' => $firstSubEvent?->end_time ?: null,
                'venue' => $firstSubEvent?->locationPoint?->name ?: ($event->venue ?: null),
                'source' => 'register',
            ]
        );
    }

    // Helper method: detect calendar overlaps.
    private function detectCalendarOverlaps(User $student, Posting $posting): array
    {
        $posting->loadMissing('event.subEvents');
        $event = $posting->event;
        if (! $event) {
            return [];
        }

        $targetSlots = $this->buildComparableSubEventSlots($event);
        if ($targetSlots === []) {
            return [];
        }

        $registeredEventIds = EventRegistration::query()
            ->where('event_registrations.student_id', $student->id)
            ->where('event_registrations.event_id', '!=', $event->id)
            ->pluck('event_registrations.event_id')
            ->all();

        $ticketEventIds = TicketPurchase::query()
            ->where('student_id', $student->id)
            ->where('event_id', '!=', $event->id)
            ->pluck('event_id')
            ->all();

        $otherEventIds = array_values(array_unique(array_merge($registeredEventIds, $ticketEventIds)));
        if ($otherEventIds === []) {
            return [];
        }

        $otherEvents = Event::query()
            ->with('subEvents')
            ->whereIn('id', $otherEventIds)
            ->get();

        $conflictsByKey = [];
        foreach ($otherEvents as $otherEvent) {
            $otherSlots = $this->buildComparableSubEventSlots($otherEvent);
            if ($otherSlots === []) {
                continue;
            }

            foreach ($targetSlots as $target) {
                foreach ($otherSlots as $other) {
                    if ($target['date'] !== $other['date']) {
                        continue;
                    }
                    if ($other['start_at']->lt($target['end_at']) && $other['end_at']->gt($target['start_at'])) {
                        $key = implode('|', [
                            $target['sub_event_title'],
                            $target['date'],
                            $target['start'],
                            $target['end'],
                            $otherEvent->name,
                            $other['sub_event_title'],
                            $other['start'],
                            $other['end'],
                        ]);
                        $conflictsByKey[$key] = [
                            'date' => $target['date'],
                            'target_sub_event_title' => $target['sub_event_title'],
                            'target_start' => $target['start'],
                            'target_end' => $target['end'],
                            'other_event_name' => $otherEvent->name,
                            'other_sub_event_title' => $other['sub_event_title'],
                            'other_start' => $other['start'],
                            'other_end' => $other['end'],
                        ];
                    }
                }
            }
        }

        return array_values($conflictsByKey);
    }

    // Helper method: build comparable sub event slots.
    private function buildComparableSubEventSlots(Event $event): array
    {
        $slots = [];
        foreach ($event->subEvents as $subEvent) {
            if (empty($subEvent->event_date) || empty($subEvent->start_time) || empty($subEvent->end_time)) {
                continue;
            }

            $date = (string) $subEvent->event_date;
            $startAt = Carbon::parse($date . ' ' . $subEvent->start_time);
            $endAt = Carbon::parse($date . ' ' . $subEvent->end_time);
            if ($endAt->lte($startAt)) {
                continue;
            }

            $slots[] = [
                'date' => $date,
                'sub_event_title' => (string) ($subEvent->title ?: 'Sub event'),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'start' => $startAt->format('H:i'),
                'end' => $endAt->format('H:i'),
            ];
        }

        return $slots;
    }

    // Helper method: build conflict message.
    private function buildConflictMessage(array $conflicts): string
    {
        if ($conflicts === []) {
            return '';
        }

        $preview = collect($conflicts)
            ->map(fn (array $conflict) => $conflict['target_sub_event_title']
                . ' (' . $conflict['target_start'] . '-' . $conflict['target_end'] . ')'
                . ' overlaps with '
                . $conflict['other_event_name'] . ' - ' . $conflict['other_sub_event_title']
                . ' (' . $conflict['other_start'] . '-' . $conflict['other_end'] . ')')
            ->implode(', ');

        return ' Schedule overlap detected with: ' . $preview . '.';
    }

    // Auth/user helper methods and reusable aggregates for posting cards.
    private function authenticatedStudent(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    // Helper method: favorite ids.
    private function favoriteIds(User $user): array
    {
        return $user->favoritePostings()
            ->pluck('postings.id')
            ->all();
    }

    // Helper method: registered posting ids.
    private function registeredPostingIds(User $user): array
    {
        $eventIds = EventRegistration::where('student_id', $user->id)
            ->pluck('event_id')
            ->filter()
            ->all();

        if ($eventIds === []) {
            return [];
        }

        return Posting::query()
            ->whereIn('event_id', $eventIds)
            ->pluck('id')
            ->all();
    }

    // Helper method: event registration counts.
    private function eventRegistrationCounts(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        return EventRegistration::query()
            ->whereIn('event_id', $eventIds)
            ->groupBy('event_id')
            ->selectRaw('event_id, COUNT(*) as total')
            ->pluck('total', 'event_id')
            ->all();
    }

    // Helper method: events where this student is already assigned as committee.
    private function committeeEventIds(User $user): array
    {
        return DB::table('event_committees')
            ->where('user_id', $user->id)
            ->pluck('event_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    // Student event posting pages (all, favorites, detail).
    public function index(Request $request)
    {
        $user = $this->authenticatedStudent();
        $filters = $this->indexFilters($request);

        $query = Posting::with(['club', 'event.ticketSetting', 'images'])
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended')
                    ->where('approval_status', 'approved');
            })
            ->latest();

        $this->applySearchAndLifecycleFilters($query, $request);
        $postings = $query->get();

        return view('user.event-posting', [
            'postings' => $postings,
            'activeTab' => 'all',
            'favoriteIds' => $this->favoriteIds($user),
            'registeredIds' => $this->registeredPostingIds($user),
            'committeeEventIds' => $this->committeeEventIds($user),
            'canRegister' => true,
            'filters' => $filters,
            'eventRegistrationCounts' => $this->eventRegistrationCounts($postings->pluck('event_id')->filter()->unique()->all()),
        ]);
    }

    // Controller action: favorites.
    public function favorites(Request $request)
    {
        $user = $this->authenticatedStudent();
        $filters = $this->indexFilters($request);

        $query = $user->favoritePostings()
            ->with(['club', 'event.ticketSetting', 'images'])
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended')
                    ->where('approval_status', 'approved');
            })
            ->latest('postings.created_at');

        $this->applySearchAndLifecycleFilters($query, $request);
        $postings = $query->get();

        return view('user.event-posting', [
            'postings' => $postings,
            'activeTab' => 'favorites',
            'favoriteIds' => $this->favoriteIds($user),
            'registeredIds' => $this->registeredPostingIds($user),
            'committeeEventIds' => $this->committeeEventIds($user),
            'canRegister' => true,
            'filters' => $filters,
            'eventRegistrationCounts' => $this->eventRegistrationCounts($postings->pluck('event_id')->filter()->unique()->all()),
        ]);
    }

    // Load and render the requested record details page.
    public function show(Posting $posting)
    {
        $user = $this->authenticatedStudent();

        $posting->load(['club', 'event.ticketSetting', 'event.luckyDraw.numbers', 'images']);
        if (($posting->event?->status ?? 'in_progress') === 'ended'
            || ($posting->event?->approval_status ?? 'approved') !== 'approved') {
            abort(404);
        }

        return view('user.event-posting-show', [
            'posting' => $posting,
            'favoriteIds' => $this->favoriteIds($user),
            'registeredIds' => $this->registeredPostingIds($user),
            'committeeEventIds' => $this->committeeEventIds($user),
            'canRegister' => true,
            'eventRegistrationCounts' => $this->eventRegistrationCounts($posting->event_id ? [$posting->event_id] : []),
        ]);
    }

    // Register flow validates posting/event status, participant limit, and sends overlap alert notification if needed.
    public function register(Posting $posting)
    {
        $user = $this->authenticatedStudent();

        if (($posting->status ?? 'open') !== 'open') {
            return redirect()
                ->back()
                ->with('status', 'Registration is unavailable for this event.');
        }
        if ($posting->outdated_at && $posting->outdated_at->lte(now())) {
            return redirect()
                ->back()
                ->with('status', 'This posting is outdated.');
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
        if ($posting->event && $posting->event->committeeMembers()->where('users.id', $user->id)->exists()) {
            return redirect()
                ->back()
                ->with('status', 'Committee members cannot register as participants for this event.');
        }
        $limit = $posting->event?->participant_limit;
        if (($posting->event?->registration_type ?? 'register') === 'ticket') {
            return redirect()
                ->back()
                ->with('status', 'This event requires a ticket purchase.');
        }
        if ($limit) {
            $currentCount = EventRegistration::where('event_id', $posting->event_id)->count();
            if ($currentCount >= $limit) {
                return redirect()
                    ->back()
                    ->with('status', 'Registration limit reached for this event.');
            }
        }

        EventRegistration::firstOrCreate([
            'event_id' => $posting->event_id,
            'student_id' => $user->id,
        ]);
        $this->syncCalendarEntry($user, $posting);
        $conflicts = $this->detectCalendarOverlaps($user, $posting);
        if ($conflicts !== []) {
            $posting->loadMissing('event');
            $user->notify(new OverlappingScheduleAlertNotification($posting, $conflicts));
        }
        $status = 'Registration submitted.' . $this->buildConflictMessage($conflicts);

        return redirect()
            ->back()
            ->with('status', $status);
    }

    // Toggle favorite for quick save/unsave from posting lists and detail.
    public function toggleFavorite(Posting $posting)
    {
        $user = $this->authenticatedStudent();

        $user->favoritePostings()->toggle($posting->id);

        return redirect()
            ->back();
    }
}
