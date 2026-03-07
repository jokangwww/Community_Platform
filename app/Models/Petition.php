<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Petition extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'proposed_solution',
        'status',
        'is_official',
        'supporter_goal',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
            'expires_at'  => 'datetime',
        ];
    }

    /* ── Relationships ─────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PetitionAttachment::class);
    }

    public function supports(): HasMany
    {
        return $this->hasMany(PetitionSupport::class);
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /* ── Computed helpers ──────────────────────────── */

    public function getSupportCountAttribute(): int
    {
        return $this->supports()->count();
    }

    public function getCommentCountAttribute(): int
    {
        return $this->supports()->whereNotNull('comment')->count();
    }

    public function hasUserSupported(int $userId): bool
    {
        return $this->supports()->where('user_id', $userId)->exists();
    }
}
