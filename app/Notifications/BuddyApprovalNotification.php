<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BuddyApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $role,
        private readonly string $status
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $roleLabel = ucfirst($this->role);

        if ($this->status === 'active') {
            return [
                'type' => 'buddy_approval',
                'role' => $this->role,
                'status' => 'approved',
                'title' => "Buddy Programme {$roleLabel} Approved",
                'message' => "Your {$this->role} application for the Buddy Programme has been approved. You can now access the programme.",
                'url' => '/buddy-programme',
            ];
        }

        return [
            'type' => 'buddy_approval',
            'role' => $this->role,
            'status' => 'rejected',
            'title' => "Buddy Programme {$roleLabel} Application Rejected",
            'message' => "Your {$this->role} application for the Buddy Programme has been rejected.",
            'url' => '/buddy-programme',
        ];
    }
}
