<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddyQuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'participant_id',
        'score',
        'total_marks',
        'answers',
        'completed_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the quiz this attempt belongs to
     */
    public function quiz()
    {
        return $this->belongsTo(BuddyQuiz::class, 'quiz_id');
    }

    /**
     * Get the participant who made this attempt
     */
    public function participant()
    {
        return $this->belongsTo(BuddyParticipant::class, 'participant_id');
    }

}
