<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddyParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'student_id',
        'course',
        'faculty',
        'year_of_study',
        'cgpa',
        'role',
        'is_repeater',
        'subject_id',
        'document_path',
        'document_name',
        'status',
        'priority_tier',
        'rating',
        'waitlist_position',
        'rejection_reason',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'is_repeater' => 'boolean',
        'cgpa' => 'decimal:2',
        'rating' => 'decimal:1',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the user associated with this participant
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subjects for this participant (legacy pivot - deprecated)
     */
    public function subjects()
    {
        return $this->belongsToMany(BuddySubject::class, 'buddy_participant_subjects');
    }

    /**
     * Get the single subject/skill for this participant
     */
    public function subject()
    {
        return $this->belongsTo(BuddySubject::class, 'subject_id');
    }

    /**
     * Get matches as mentor
     */
    public function mentorMatches()
    {
        return $this->hasMany(BuddyMatch::class, 'mentor_id');
    }

    /**
     * Get matches as mentee
     */
    public function menteeMatches()
    {
        return $this->hasMany(BuddyMatch::class, 'mentee_id');
    }

    /**
     * Get all matches (as mentor or mentee) - via pivot table
     */
    public function matches()
    {
        return $this->belongsToMany(BuddyMatch::class, 'buddy_match_participants', 'participant_id', 'match_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Scope for mentors
     */
    public function scopeMentors($query)
    {
        return $query->where('role', 'mentor');
    }

    /**
     * Scope for mentees
     */
    public function scopeMentees($query)
    {
        return $query->where('role', 'mentee');
    }

    /**
     * Scope for active participants
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if participant is a mentor
     */
    public function isMentor(): bool
    {
        return $this->role === 'mentor';
    }

    /**
     * Check if participant is a mentee
     */
    public function isMentee(): bool
    {
        return $this->role === 'mentee';
    }

}
