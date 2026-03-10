<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddyMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester_id',
        'mentor_id',
        'mentee_id',
        'subject_id',
        'matched_date',
        'status',
        'total_sessions',
        'completed_sessions',
    ];

    protected $casts = [
        'matched_date' => 'date',
    ];

    /**
     * Get the semester this match belongs to
     */
    public function semester()
    {
        return $this->belongsTo(BuddySemesterSetting::class, 'semester_id');
    }

    /**
     * Get all participants in this match (many-to-many)
     */
    public function participants()
    {
        return $this->belongsToMany(BuddyParticipant::class, 'buddy_match_participants', 'match_id', 'participant_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get only mentors in this match
     */
    public function mentors()
    {
        return $this->participants()->wherePivot('role', 'mentor');
    }

    /**
     * Get only mentees in this match
     */
    public function mentees()
    {
        return $this->participants()->wherePivot('role', 'mentee');
    }

    /**
     * Get the mentor for this match (legacy - for backward compatibility)
     */
    public function mentor()
    {
        return $this->belongsTo(BuddyParticipant::class, 'mentor_id');
    }

    /**
     * Get the mentee for this match (legacy - for backward compatibility)
     */
    public function mentee()
    {
        return $this->belongsTo(BuddyParticipant::class, 'mentee_id');
    }

    /**
     * Get the subject for this match
     */
    public function subject()
    {
        return $this->belongsTo(BuddySubject::class, 'subject_id');
    }

    /**
     * Get all sessions for this match
     */
    public function sessions()
    {
        return $this->hasMany(BuddySession::class, 'match_id');
    }

    /**
     * Scope for active matches
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

}
