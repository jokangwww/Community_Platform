<?php

namespace App\Services;

use App\Models\EventRegistration;
use App\Notifications\EventStartingSoonNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventNotificationService
{
    /**
     * Send reminders for registered students when a sub-event starts within the given time window.
     *
     * Returns the number of reminders sent.
     */
    public function sendStartingSoonNotifications(int $hours = 24): int
    {
        $now = now();
        $windowEnd = $now->copy()->addHours(max($hours, 1));
        $sent = 0;

        // Preload related records to avoid repeated queries inside the loop.
        $registrations = EventRegistration::with(['student', 'event.subEvents', 'event.postings'])->get();

        foreach ($registrations as $registration) {
            $student = $registration->student;
            $event = $registration->event;
            $posting = $event?->postings?->sortByDesc('created_at')->first();

            // Skip incomplete or ineligible registrations.
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
                // Prevent duplicate reminders per registration + sub-event.
                $alreadySent = DB::table('event_registration_reminders')
                    ->where('event_registration_id', $registration->id)
                    ->where('sub_event_id', $target->sub_event_id)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $student->notify(new EventStartingSoonNotification(
                    $posting,
                    $event,
                    $target->start_at,
                    $target->title
                ));

                // Persist reminder log for idempotency on future runs.
                DB::table('event_registration_reminders')->insert([
                    'event_registration_id' => $registration->id,
                    'sub_event_id' => $target->sub_event_id,
                    'reminded_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Build and return upcoming sub-events that start within [now, windowEnd].
     */
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
                // If no start time is set, treat it as midnight of the event date.
                $time = !empty($subEvent->start_time) ? (string) $subEvent->start_time : '00:00:00';
                $startAt = Carbon::parse($subEvent->event_date . ' ' . $time);

                return (object) [
                    'sub_event_id' => $subEvent->id,
                    'title' => (string) ($subEvent->title ?? ''),
                    'start_at' => $startAt,
                ];
            })
            ->filter()
            ->filter(fn ($item) => $item->start_at->betweenIncluded($now, $windowEnd))
            ->sortBy(fn ($item) => $item->start_at->timestamp)
            ->values();
    }
}
