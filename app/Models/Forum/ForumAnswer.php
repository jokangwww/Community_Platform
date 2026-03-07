<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ForumAnswer extends Model
{
    use HasFactory;

    protected $table = 'forum_answers';

    protected $fillable = [
        'post_id',
        'user_id',
        'content',
        'upvotes_count',
        'downvotes_count',
        'is_accepted',
    ];

    protected function casts(): array
    {
        return [
            'is_accepted' => 'boolean',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ForumVote::class, 'answer_id');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(ForumReaction::class, 'reactable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(ForumReport::class, 'reportable');
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(ForumMention::class, 'mentionable');
    }

    /**
     * Get user's vote on this answer
     */
    public function getUserVote(?int $userId): ?string
    {
        if (!$userId) return null;
        $vote = $this->votes()->where('user_id', $userId)->first();
        return $vote?->vote_type;
    }
}
