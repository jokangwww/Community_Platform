<?php

namespace App\Services;

use App\Models\EventRegistration;
use App\Notifications\EventStartingSoonNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventNotificationService
{
    public function sendStartingSoonNotifications(int $hours = 24): int
    {
        $now = now();
        $windowEnd = $now->copy()->addHours(max($hours, 1));
        $sent = 0;

        $registrations = EventRegistration::with(['student', 'event.subEvents', 'event.postings'])->get();

        foreach ($registrations as $registration) {
            $student = $registration->student;
            $event = $registration->event;
            $posting = $event?->postings?->sortByDesc('created_at')->first();

            if (! $student || ! $posting || ! $event) {
                continue;
            }
            if (($student->role ?? null) !== 'student') {
                continue;
            }
            if (($event->status ?? 'in_progress') === 'ended') {
                continue;
            }

            $reminderTargets = $this->resolveSoonReminderTargets(
                $event->subEvents,
                $now,
                $windowEnd
            );
            if ($reminderTargets->isEmpty()) {
                continue;
            }

            foreach ($reminderTargets as $target) {
                $alreadySent = DB::table('event_registration_reminders')
                    ->where('event_registration_id', $registration->id)
                    ->where('sub_event_id', $target['sub_event_id'])
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $student->notify(new EventStartingSoonNotification(
                    $posting,
                    $event,
                    $target['start_at'],
                    $target['title']
                ));

                DB::table('event_registration_reminders')->insert([
                    'event_registration_id' => $registration->id,
                    'sub_event_id' => $target['sub_event_id'],
                    'reminded_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $sent++;
            }
        }

        return $sent;
    }

    private function resolveSoonReminderTargets(
        Collection $subEvents,
        Carbon $now,
        Carbon $windowEnd
    ): Collection
    {
        return $subEvents
            ->map(function ($subEvent) {
                if (empty($subEvent->event_date)) {
                    return null;
                }
                $time = !empty($subEvent->start_time) ? (string) $subEvent->start_time : '00:00:00';
                $startAt = Carbon::parse($subEvent->event_date . ' ' . $time);

                return [
                    'sub_event_id' => $subEvent->id,
                    'title' => (string) ($subEvent->title ?? ''),
                    'start_at' => $startAt,
                ];
            })
            ->filter()
            ->filter(fn (array $item) => $item['start_at']->betweenIncluded($now, $windowEnd))
            ->sortBy(fn (array $item) => $item['start_at']->timestamp)
            ->values();
    }
}
