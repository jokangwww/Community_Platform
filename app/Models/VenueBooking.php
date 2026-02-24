<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'venue_id',
        'event_title',
        'event_details',
        'start_at',
        'end_at',
        'status',
        'admin_review_reason',
        'admin_reviewed_by',
        'admin_reviewed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'admin_reviewed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(User::class, 'club_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_reviewed_by');
    }

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'approved']);
    }

    public function scopeOverlappingRange(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->where('start_at', '<', $end)
            ->where('end_at', '>', $start);
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'approved' && $this->end_at && $this->end_at->isPast()) {
            return 'completed';
        }

        return $this->status;
    }
}

