<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Poll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'expires_at',
        'target_faculty',
        'target_year',
        'target_course',
        'status',
        'is_official',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_official' => 'boolean',
        ];
    }

    /* ── Relationships ─────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('position');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(PollRating::class);
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
              ->orWhere('expires_at', '<=', now());
        });
    }

    /* ── Computed helpers ──────────────────────────── */

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at <= now() || $this->status === 'expired';
    }

    public function getTotalVotesAttribute(): int
    {
        return $this->votes()->count();
    }

    public function getUsefulnessScoreAttribute(): ?float
    {
        $total = $this->ratings()->count();
        if ($total === 0) return null;
        $useful = $this->ratings()->where('is_useful', true)->count();
        return round(($useful / $total) * 100, 1);
    }

    public function hasUserVoted(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    public function hasUserRated(int $userId): bool
    {
        return $this->ratings()->where('user_id', $userId)->exists();
    }
}
