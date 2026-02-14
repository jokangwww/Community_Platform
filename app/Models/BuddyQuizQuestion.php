<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddyQuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question',
        'options',
        'correct_answer',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Get the quiz this question belongs to
     */
    public function quiz()
    {
        return $this->belongsTo(BuddyQuiz::class, 'quiz_id');
    }
}
