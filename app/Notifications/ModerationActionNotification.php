<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ModerationActionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $action,
        private string $reason,
        private string $contentType,
        private ?string $note = null,
        private ?int $muteDurationDays = null,
        private int $totalWarnings = 0,
        private int $totalMutes = 0,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $title = match ($this->action) {
            'warn'   => 'Warning: Your content has been flagged',
            'mute'   => 'Account Muted',
            'delete' => 'Your content has been removed',
            default  => 'Moderation Notice',
        };

        $message = match ($this->action) {
            'warn'   => "Your {$this->contentType} was flagged for \"{$this->reason}\". "
                      . "This is warning #{$this->totalWarnings}. Repeated violations may result in muting your account.",
            'mute'   => "Your account has been muted for {$this->muteDurationDays} day(s) due to \"{$this->reason}\". "
                      . "This is mute #{$this->totalMutes}. You cannot post or comment while muted.",
            'delete' => "Your {$this->contentType} was removed for violating community guidelines (reason: \"{$this->reason}\").",
            default  => "A moderation action was taken regarding your {$this->contentType}.",
        };

        return [
            'title'              => $title,
            'message'            => $message,
            'action'             => $this->action,
            'reason'             => $this->reason,
            'content_type'       => $this->contentType,
            'note'               => $this->note,
            'mute_duration_days' => $this->muteDurationDays,
            'total_warnings'     => $this->totalWarnings,
            'total_mutes'        => $this->totalMutes,
            'type'               => 'moderation_action',
            'url'                => '/forum',
        ];
    }
}
