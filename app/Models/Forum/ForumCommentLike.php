<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumCommentLike extends Model
{
    use HasFactory;

    protected $table = 'forum_comment_likes';

    protected $fillable = [
        'user_id',
        'comment_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ForumComment::class, 'comment_id');
    }
}
