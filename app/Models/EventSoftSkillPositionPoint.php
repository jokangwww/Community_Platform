<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSoftSkillPositionPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_soft_skill_setting_id',
        'position_name',
        'points',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(EventSoftSkillSetting::class, 'event_soft_skill_setting_id');
    }
}
