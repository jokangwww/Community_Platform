<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'session_date',
        'session_time',
        'session_end_time',
        'topic',
        'description',
        'status',
        'mentor_check_in',
        'mentee_check_in',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'mentor_check_in' => 'datetime',
        'mentee_check_in' => 'datetime',
    ];

    /**
     * Get the match for this session
     */
    public function match()
    {
        return $this->belongsTo(BuddyMatch::class, 'match_id');
    }

    /**
     * Scope for scheduled sessions
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope for completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for upcoming sessions
     */
    public function scopeUpcoming($query)
    {
        return $query->where('session_date', '>=', now()->toDateString())
                     ->where('status', 'scheduled');
    }

}
