<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ForumMentionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $mentionedByName,
        private string $contentType,
        private string $postTitle,
        private int $postId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $contextLabel = match ($this->contentType) {
            'post'    => 'a post',
            'comment' => 'a comment',
            'answer'  => 'an answer',
            default   => 'a post',
        };

        return [
            'title'        => 'You were mentioned',
            'message'      => "{$this->mentionedByName} mentioned you in {$contextLabel} on \"{$this->postTitle}\"",
            'type'         => 'forum_mention',
            'content_type' => $this->contentType,
            'post_id'      => $this->postId,
            'url'          => '/forum',
        ];
    }
}
