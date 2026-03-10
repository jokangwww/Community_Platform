<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    protected $fillable = [
        'user_id',
        'bookmarkable_type',
        'bookmarkable_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bookmarkable model (Poll or Petition).
     */
    public function bookmarkable()
    {
        return $this->morphTo('bookmarkable', 'bookmarkable_type', 'bookmarkable_id');
    }

    /**
     * Resolve the actual model class from the simple type string.
     */
    public static function resolveModelClass(string $type): ?string
    {
        return match ($type) {
            'poll' => Poll::class,
            'petition' => Petition::class,
            default => null,
        };
    }

    /**
     * Check if a user has bookmarked a specific item.
     */
    public static function isBookmarked(int $userId, string $type, int $id): bool
    {
        return self::where('user_id', $userId)
            ->where('bookmarkable_type', $type)
            ->where('bookmarkable_id', $id)
            ->exists();
    }

    /**
     * Toggle bookmark for a user. Returns true if bookmarked, false if removed.
     */
    public static function toggle(int $userId, string $type, int $id): bool
    {
        $existing = self::where('user_id', $userId)
            ->where('bookmarkable_type', $type)
            ->where('bookmarkable_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();
            return false;
        }

        self::create([
            'user_id' => $userId,
            'bookmarkable_type' => $type,
            'bookmarkable_id' => $id,
        ]);

        return true;
    }
}
