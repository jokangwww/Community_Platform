<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ForumPost extends Model
{
    use HasFactory;

    protected $table = 'forum_posts';

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'content',
        'views',
        'likes_count',
        'answer_count',
        'comment_count',
        'has_accepted_answer',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'has_accepted_answer' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'category_id');
    }

    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(ForumHashtag::class, 'forum_post_hashtag', 'post_id', 'hashtag_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ForumPostAttachment::class, 'post_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ForumAnswer::class, 'post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ForumComment::class, 'post_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ForumPostLike::class, 'post_id');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(ForumReport::class, 'reportable');
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(ForumMention::class, 'mentionable');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(ForumReaction::class, 'reactable');
    }

    /**
     * Check if a user has liked this post
     */
    public function isLikedBy(?int $userId): bool
    {
        if (!$userId) return false;
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Scope for active posts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to search by keyword
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
              ->orWhere('content', 'like', "%{$keyword}%");
        });
    }
}
