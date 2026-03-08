<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LuckyDrawWinnerNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Event $event,
        private readonly int $luckyDrawNumber,
        private readonly ?string $ticketNumber = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $eventName = (string) ($this->event->name ?? 'Event');
        $ticketLabel = $this->ticketNumber ? ' (Ticket: ' . $this->ticketNumber . ')' : '';

        return [
            'type' => 'lucky_draw_winner',
            'event_id' => $this->event->id,
            'event_name' => $eventName,
            'lucky_draw_number' => $this->luckyDrawNumber,
            'ticket_number' => $this->ticketNumber,
            'title' => 'Lucky Draw Winner',
            'message' => 'Congratulations! You won lucky draw number '
                . $this->luckyDrawNumber
                . ' for "'
                . $eventName
                . "\"{$ticketLabel}.",
            'url' => route('user.lucky-draw'),
        ];
    }
}

