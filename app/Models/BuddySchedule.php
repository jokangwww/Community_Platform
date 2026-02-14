<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'selected_slot_id',
        'day',
        'start_time',
        'end_time',
        'total_votes',
        'status',
    ];

    /**
     * Get the match this schedule belongs to
     */
    public function match()
    {
        return $this->belongsTo(BuddyMatch::class, 'match_id');
    }

    /**
     * Get the selected time slot
     */
    public function selectedSlot()
    {
        return $this->belongsTo(BuddyTimeSlot::class, 'selected_slot_id');
    }

    /**
     * Get formatted time string
     */
    public function getFormattedTimeAttribute(): string
    {
        $start = date('g:i A', strtotime($this->start_time));
        $end = date('g:i A', strtotime($this->end_time));
        return "{$start} - {$end}";
    }

}
