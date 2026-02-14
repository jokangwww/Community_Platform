<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuddyEvaluation extends Model
{
    protected $fillable = [
        'match_id',
        'from_participant_id',
        'to_participant_id',
        'from_role',
        'to_role',
        'rating',
        'feedback',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the match this evaluation belongs to
     */
    public function match(): BelongsTo
    {
        return $this->belongsTo(BuddyMatch::class, 'match_id');
    }

    /**
     * Get the participant who submitted this evaluation
     */
    public function fromParticipant(): BelongsTo
    {
        return $this->belongsTo(BuddyParticipant::class, 'from_participant_id');
    }

    /**
     * Get the participant who received this evaluation
     */
    public function toParticipant(): BelongsTo
    {
        return $this->belongsTo(BuddyParticipant::class, 'to_participant_id');
    }
}
