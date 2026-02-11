<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'event_id',
        'event_name',
        'event_date',
        'event_start_time',
        'event_end_time',
        'venue',
        'source',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
