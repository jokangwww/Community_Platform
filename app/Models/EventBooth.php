<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBooth extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_booth_place_id',
        'name',
    ];

    public function boothPlace(): BelongsTo
    {
        return $this->belongsTo(EventBoothPlace::class, 'event_booth_place_id');
    }
}

