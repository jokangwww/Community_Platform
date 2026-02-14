<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddyTimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'day',
        'start_time',
        'end_time',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Get the match this time slot belongs to
     */
    public function match()
    {
        return $this->belongsTo(BuddyMatch::class, 'match_id');
    }

    /**
     * Get all votes for this time slot
     */
    public function votes()
    {
        return $this->hasMany(BuddyTimeSlotVote::class, 'time_slot_id');
    }

    /**
     * Get formatted start time
     */
    public function getFormattedStartTimeAttribute(): string
    {
        return date('g:i A', strtotime($this->start_time));
    }

    /**
     * Get formatted end time
     */
    public function getFormattedEndTimeAttribute(): string
    {
        return date('g:i A', strtotime($this->end_time));
    }
}
