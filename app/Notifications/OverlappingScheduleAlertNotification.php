<?php

namespace App\Notifications;

use App\Models\Posting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OverlappingScheduleAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Posting $posting,
        private readonly array $conflicts
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $eventName = (string) ($this->posting->event?->name ?? 'Event');
        $preview = collect($this->conflicts)
            ->map(fn (array $item) => $item['target_sub_event_title']
                . ' (' . $item['target_start'] . '-' . $item['target_end'] . ')'
                . ' with '
                . $item['other_event_name'] . ' - ' . $item['other_sub_event_title']
                . ' (' . $item['other_start'] . '-' . $item['other_end'] . ')')
            ->implode(', ');

        return [
            'type' => 'overlap_alert',
            'posting_id' => $this->posting->id,
            'event_name' => $eventName,
            'title' => 'Schedule Overlap Alert',
            'message' => "Your registration for \"{$eventName}\" has overlaps: {$preview}.",
            'conflicts' => $this->conflicts,
            'url' => route('user.event-posting.show', $this->posting),
        ];
    }
}
