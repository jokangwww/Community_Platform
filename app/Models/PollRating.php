<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'user_id',
        'is_useful',
    ];

    protected function casts(): array
    {
        return [
            'is_useful' => 'boolean',
        ];
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
