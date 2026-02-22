<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\Posting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventStartingSoonNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Posting $posting,
        private readonly Event $event,
        private readonly Carbon $startAt,
        private readonly ?string $subEventTitle = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $eventName = (string) ($this->event->name ?? 'Event');
        $startLabel = $this->startAt->format('Y-m-d H:i');
        $subEventLabel = $this->subEventTitle ? " ({$this->subEventTitle})" : '';

        return [
            'type' => 'event_starting_soon',
            'posting_id' => $this->posting->id,
            'event_id' => $this->event->id,
            'event_name' => $eventName,
            'start_at' => $this->startAt->toDateTimeString(),
            'title' => 'Event starting soon',
            'message' => "\"{$eventName}\"{$subEventLabel} starts soon at {$startLabel}.",
            'url' => route('user.event-posting.show', $this->posting),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventName = (string) ($this->event->name ?? 'Event');
        $subEventLabel = $this->subEventTitle ? " ({$this->subEventTitle})" : '';

        return (new MailMessage())
            ->subject('Event Starting Soon: ' . $eventName)
            ->line("\"{$eventName}\"{$subEventLabel} is starting soon.")
            ->line('Start time: ' . $this->startAt->format('Y-m-d H:i'))
            ->line('Please check the portal for details.');
    }
}
