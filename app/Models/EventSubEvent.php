<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSubEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'title',
        'location_point_id',
        'event_date',
        'start_time',
        'end_time',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function locationPoint()
    {
        return $this->belongsTo(LocationPoint::class, 'location_point_id');
    }
}
