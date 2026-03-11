<?php

namespace App\Notifications;

use App\Models\BuddyMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BuddyMatchedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BuddyMatch $match,
        private readonly string $partnerName,
        private readonly string $subjectName,
        private readonly string $role
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $partnerRole = $this->role === 'mentor' ? 'mentee' : 'mentor';

        return [
            'type' => 'buddy_matched',
            'match_id' => $this->match->id,
            'partner_name' => $this->partnerName,
            'subject' => $this->subjectName,
            'title' => 'Buddy Programme Match Found',
            'message' => "You have been matched with {$partnerRole} {$this->partnerName} for {$this->subjectName}.",
            'url' => '/buddy-programme',
        ];
    }
}
