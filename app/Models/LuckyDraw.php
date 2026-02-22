<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LuckyDraw extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'range_start',
        'range_end',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function numbers(): HasMany
    {
        return $this->hasMany(LuckyDrawNumber::class)->orderBy('number');
    }

    public function excludedNumbers(): HasMany
    {
        return $this->hasMany(LuckyDrawNumber::class)->where('type', 'excluded')->orderBy('number');
    }

    public function winningNumbers(): HasMany
    {
        return $this->hasMany(LuckyDrawNumber::class)->where('type', 'winning')->orderBy('number');
    }
}
