<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'recruitment_id',
        'student_id',
        'phone',
        'skills',
        'experience',
        'status',
        'reply',
    ];

    public function recruitment()
    {
        return $this->belongsTo(Recruitment::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers()
    {
        return $this->hasMany(RecruitmentApplicationAnswer::class);
    }
}
