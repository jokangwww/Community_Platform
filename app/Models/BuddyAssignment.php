<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddyAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'created_by',
        'title',
        'description',
        'due_date',
        'total_marks',
        'attachments',
    ];

    protected $casts = [
        'due_date' => 'date',
        'attachments' => 'array',
    ];

    /**
     * Get the match this assignment belongs to
     */
    public function match()
    {
        return $this->belongsTo(BuddyMatch::class, 'match_id');
    }

    /**
     * Get the participant who created this assignment
     */
    public function creator()
    {
        return $this->belongsTo(BuddyParticipant::class, 'created_by');
    }

    /**
     * Get all submissions for this assignment
     */
    public function submissions()
    {
        return $this->hasMany(BuddyAssignmentSubmission::class, 'assignment_id');
    }

}
