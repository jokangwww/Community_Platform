<?php

namespace App\Notifications;

use App\Models\Posting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FavoritePostingRegistrationOpenedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Posting $posting)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        $eventName = (string) ($this->posting->event?->name ?? 'Event');

        return [
            'type' => 'favorite_registration_opened',
            'posting_id' => $this->posting->id,
            'event_name' => $eventName,
            'title' => 'Registration is now open',
            'message' => "Registration for your favorite event \"{$eventName}\" is now open.",
            'url' => route('user.event-posting.show', $this->posting),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventName = (string) ($this->posting->event?->name ?? 'Event');

        return (new MailMessage())
            ->subject('Registration Open: ' . $eventName)
            ->line("Registration for your favorite event \"{$eventName}\" is now open.")
            ->line('Please check the portal for details.');
    }
}
