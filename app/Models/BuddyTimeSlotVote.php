<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddyTimeSlotVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'time_slot_id',
        'participant_id',
    ];

    /**
     * Get the time slot this vote is for
     */
    public function timeSlot()
    {
        return $this->belongsTo(BuddyTimeSlot::class, 'time_slot_id');
    }

    /**
     * Get the participant who voted
     */
    public function participant()
    {
        return $this->belongsTo(BuddyParticipant::class, 'participant_id');
    }
}
