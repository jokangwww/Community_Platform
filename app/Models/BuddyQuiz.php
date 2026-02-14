<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddyQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'created_by',
        'title',
        'total_marks',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * Get the match this quiz belongs to
     */
    public function match()
    {
        return $this->belongsTo(BuddyMatch::class, 'match_id');
    }

    /**
     * Get the participant who created this quiz
     */
    public function creator()
    {
        return $this->belongsTo(BuddyParticipant::class, 'created_by');
    }

    /**
     * Get the questions for this quiz
     */
    public function questions()
    {
        return $this->hasMany(BuddyQuizQuestion::class, 'quiz_id')->orderBy('order');
    }

    /**
     * Get all attempts for this quiz
     */
    public function attempts()
    {
        return $this->hasMany(BuddyQuizAttempt::class, 'quiz_id');
    }

    /**
     * Scope for open quizzes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope for closed quizzes
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}
