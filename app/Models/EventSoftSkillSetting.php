<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSoftSkillSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'participant_points',
        'volunteer_base_points',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function positionPoints(): HasMany
    {
        return $this->hasMany(EventSoftSkillPositionPoint::class, 'setting_id')
            ->orderBy('position_name');
    }
}
