<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recruitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'event_id',
        'title',
        'description',
        'requirements',
        'required_skills',
        'interests',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function club()
    {
        return $this->belongsTo(User::class, 'club_id');
    }

    public function questions()
    {
        return $this->hasMany(RecruitmentQuestion::class)->orderBy('position');
    }

    public function applications()
    {
        return $this->hasMany(RecruitmentApplication::class);
    }
}
