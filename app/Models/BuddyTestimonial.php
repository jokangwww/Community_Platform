<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuddyTestimonial extends Model
{
    protected $fillable = [
        'participant_id',
        'semester_id',
        'semester_year',
        'total_sessions',
        'total_mentees',
        'skills_taught',
        'avg_feedback_score',
        'attendance_rate',
        'status',
        'rejection_reason',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'skills_taught' => 'array',
        'total_sessions' => 'integer',
        'total_mentees' => 'integer',
        'avg_feedback_score' => 'decimal:2',
        'attendance_rate' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Get the participant who requested this testimonial
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(BuddyParticipant::class, 'participant_id');
    }
}
