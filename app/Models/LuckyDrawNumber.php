<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LuckyDrawNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'lucky_draw_id',
        'type',
        'number',
    ];

    public function luckyDraw(): BelongsTo
    {
        return $this->belongsTo(LuckyDraw::class);
    }
}
