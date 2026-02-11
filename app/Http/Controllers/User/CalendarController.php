<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\StudentCalendarEvent;
use App\Models\TicketPurchase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    private function requireStudent(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    private function syncEventToCalendar(User $student, Event $event, string $source): void
    {
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
                'source' => $source,
            ]
        );
    }

    private function syncJoinedEvents(User $student): void
    {
        $eventIds = $this->joinedEventIds($student);
        if ($eventIds === []) {
            return;
        }

        $events = Event::with('subEvents')
            ->whereIn('id', $eventIds)
            ->get()
            ->keyBy('id');

        $registeredEventIds = EventRegistration::query()
            ->join('postings', 'event_registrations.posting_id', '=', 'postings.id')
            ->where('event_registrations.student_id', $student->id)
            ->pluck('postings.event_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $ticketEventIds = TicketPurchase::query()
            ->where('student_id', $student->id)
            ->pluck('event_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($registeredEventIds as $eventId) {
            $event = $events->get($eventId);
            if ($event) {
                $this->syncEventToCalendar($student, $event, 'register');
            }
        }

        foreach ($ticketEventIds as $eventId) {
            $event = $events->get($eventId);
            if ($event) {
                $this->syncEventToCalendar($student, $event, 'ticket');
            }
        }
    }

    private function joinedEventIds(User $student): array
    {
        $registeredEventIds = EventRegistration::query()
            ->join('postings', 'event_registrations.posting_id', '=', 'postings.id')
            ->where('event_registrations.student_id', $student->id)
            ->pluck('postings.event_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $ticketEventIds = TicketPurchase::query()
            ->where('student_id', $student->id)
            ->pluck('event_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return array_values(array_unique(array_merge($registeredEventIds, $ticketEventIds)));
    }

    private function buildJoinedRows(Collection $events): Collection
    {
        $rows = collect();
        $today = now()->toDateString();

        foreach ($events as $event) {
            if ($event->subEvents->isNotEmpty()) {
                foreach ($event->subEvents as $subEvent) {
                    $date = $subEvent->event_date ?: $event->start_date;
                    $rows->push([
                        'date' => $date,
                        'event_name' => $event->name,
                        'subevent_title' => $subEvent->title,
                        'status' => $date ? ($date < $today ? 'passed' : 'not_passed') : 'unknown',
                    ]);
                }
                continue;
            }

            $date = $event->start_date;
            $rows->push([
                'date' => $date,
                'event_name' => $event->name,
                'subevent_title' => 'No subevent title',
                'status' => $date ? ($date < $today ? 'passed' : 'not_passed') : 'unknown',
            ]);
        }

        return $rows
            ->sortBy([
                fn (array $row) => $row['date'] === null ? 1 : 0,
                fn (array $row) => $row['date'] ?? '9999-12-31',
                fn (array $row) => strtolower($row['event_name']),
            ])
            ->values();
    }

    private function filterJoinedRows(Collection $rows, string $filter): Collection
    {
        if ($filter === 'passed') {
            return $rows->where('status', 'passed')->values();
        }

        if ($filter === 'not_passed') {
            return $rows->filter(function (array $row) {
                return in_array($row['status'], ['not_passed', 'unknown'], true);
            })->values();
        }

        return $rows;
    }

    public function index(Request $request): View
    {
        $student = $this->requireStudent();
        $joinedFilter = (string) $request->query('joined_filter', 'all');
        if (! in_array($joinedFilter, ['all', 'passed', 'not_passed'], true)) {
            $joinedFilter = 'all';
        }

        $this->syncJoinedEvents($student);

        $monthInput = (string) $request->query('month', '');
        try {
            $month = $monthInput !== ''
                ? Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable $e) {
            $month = now()->startOfMonth();
        }

        $calendarStart = $month->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $entries = StudentCalendarEvent::with('event.subEvents')
            ->where('student_id', $student->id)
            ->whereNotNull('event_date')
            ->whereBetween('event_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->orderBy('event_date')
            ->orderBy('event_start_time')
            ->orderBy('event_name')
            ->get();

        $entriesByDate = $entries->groupBy(function (StudentCalendarEvent $entry) {
            return $entry->event_date?->format('Y-m-d');
        });

        $days = [];
        $cursor = $calendarStart->copy();
        while ($cursor->lte($calendarEnd)) {
            $days[] = [
                'date' => $cursor->toDateString(),
                'day' => $cursor->day,
                'isCurrentMonth' => $cursor->month === $month->month,
                'isToday' => $cursor->isToday(),
            ];
            $cursor->addDay();
        }

        $allJoinedEventIds = $this->joinedEventIds($student);
        $allJoinedEvents = $allJoinedEventIds === []
            ? collect()
            : Event::with('subEvents')
                ->whereIn('id', $allJoinedEventIds)
                ->get();

        return view('user.calendar', [
            'monthLabel' => $month->format('F Y'),
            'currentMonth' => $month->format('Y-m'),
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'joinedFilter' => $joinedFilter,
            'weekdays' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'days' => $days,
            'entriesByDate' => $entriesByDate,
            'joinedRows' => $this->filterJoinedRows($this->buildJoinedRows($allJoinedEvents), $joinedFilter),
        ]);
    }
}
