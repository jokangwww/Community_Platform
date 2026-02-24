<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftSkillCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'participant_cs',
        'participant_ctps',
        'participant_ts',
        'participant_ll',
        'participant_kk',
        'participant_em',
        'participant_ls',
    ];

    public function positionRules(): HasMany
    {
        return $this->hasMany(SoftSkillCategoryPositionRule::class)
            ->orderBy('position_name');
    }
}
