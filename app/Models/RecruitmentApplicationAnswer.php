<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentApplicationAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'recruitment_application_id',
        'recruitment_question_id',
        'answer',
    ];

    public function application()
    {
        return $this->belongsTo(RecruitmentApplication::class, 'recruitment_application_id');
    }
}
