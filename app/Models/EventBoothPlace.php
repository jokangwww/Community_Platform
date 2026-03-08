<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventBoothPlace extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'start_date',
        'end_date',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function booths(): HasMany
    {
        return $this->hasMany(EventBooth::class)->orderBy('name');
    }
}
