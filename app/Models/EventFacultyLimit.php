<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventFacultyLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'faculty_name',
        'limit',
    ];
}
