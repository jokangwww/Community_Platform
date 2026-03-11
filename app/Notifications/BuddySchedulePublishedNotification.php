<?php

namespace App\Notifications;

use App\Models\BuddyMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BuddySchedulePublishedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BuddyMatch $match,
        private readonly string $mentorName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'buddy_schedule_published',
            'match_id' => $this->match->id,
            'mentor_name' => $this->mentorName,
            'title' => 'Time Slots Published for Voting',
            'message' => "Your mentor {$this->mentorName} has published meeting time slots. Please vote for your preferred time.",
            'url' => '/buddy-programme',
        ];
    }
}
