<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\TicketPurchase;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class JoinedEventController extends Controller
{
    private function requireStudent(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
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

    private function buildRows(Collection $events): Collection
    {
        $rows = collect();

        foreach ($events as $event) {
            if ($event->subEvents->isNotEmpty()) {
                foreach ($event->subEvents as $subEvent) {
                    $rows->push([
                        'date' => $subEvent->event_date ?: $event->start_date,
                        'event_name' => $event->name,
                        'subevent_title' => $subEvent->title,
                    ]);
                }
                continue;
            }

            $rows->push([
                'date' => $event->start_date,
                'event_name' => $event->name,
                'subevent_title' => 'No subevent title',
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

    public function index(): View
    {
        $student = $this->requireStudent();
        $eventIds = $this->joinedEventIds($student);

        $events = $eventIds === []
            ? collect()
            : Event::with('subEvents')
                ->whereIn('id', $eventIds)
                ->get();

        return view('user.joined-events', [
            'rows' => $this->buildRows($events),
        ]);
    }
}
