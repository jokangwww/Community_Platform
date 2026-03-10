<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuddyContinuation extends Model
{
    protected $fillable = [
        'match_id',
        'mentee_participant_id',
        'mentor_participant_id',
        'from_semester_id',
        'to_semester_id',
        'mentee_choice',
        'mentor_choice',
        'new_subject_id',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * The original match this continuation is based on
     */
    public function match()
    {
        return $this->belongsTo(BuddyMatch::class, 'match_id');
    }

    /**
     * The mentee participant (from the ending semester)
     */
    public function menteeParticipant()
    {
        return $this->belongsTo(BuddyParticipant::class, 'mentee_participant_id');
    }

    /**
     * The mentor participant (from the ending semester)
     */
    public function mentorParticipant()
    {
        return $this->belongsTo(BuddyParticipant::class, 'mentor_participant_id');
    }

    /**
     * The semester being ended
     */
    public function fromSemester()
    {
        return $this->belongsTo(BuddySemesterSetting::class, 'from_semester_id');
    }

    /**
     * The new semester being continued into
     */
    public function toSemester()
    {
        return $this->belongsTo(BuddySemesterSetting::class, 'to_semester_id');
    }

    /**
     * Optional new subject requested for the carry-forward match
     */
    public function newSubject()
    {
        return $this->belongsTo(BuddySubject::class, 'new_subject_id');
    }

    /**
     * Whether both sides have responded
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Whether both sides agreed to continue
     */
    public function isMutualContinue(): bool
    {
        return $this->mentee_choice === 'continue' && $this->mentor_choice === 'continue';
    }

    /**
     * Whether either side declined
     */
    public function isDeclined(): bool
    {
        return $this->mentee_choice === 'decline' || $this->mentor_choice === 'decline';
    }
}
