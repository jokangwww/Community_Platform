<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'recruitment_id',
        'question',
        'position',
    ];

    public function recruitment()
    {
        return $this->belongsTo(Recruitment::class);
    }
}
