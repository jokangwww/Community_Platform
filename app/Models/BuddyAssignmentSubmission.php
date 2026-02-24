<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddyAssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'participant_id',
        'file_name',
        'file_path',
        'status',
        'marks',
        'feedback',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the assignment this submission belongs to
     */
    public function assignment()
    {
        return $this->belongsTo(BuddyAssignment::class, 'assignment_id');
    }

    /**
     * Get the participant who made this submission
     */
    public function participant()
    {
        return $this->belongsTo(BuddyParticipant::class, 'participant_id');
    }

}
