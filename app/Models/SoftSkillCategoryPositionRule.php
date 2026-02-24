<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftSkillCategoryPositionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'soft_skill_category_id',
        'position_name',
        'cs',
        'ctps',
        'ts',
        'll',
        'kk',
        'em',
        'ls',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SoftSkillCategory::class, 'soft_skill_category_id');
    }
}
