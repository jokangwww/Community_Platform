<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetitionSupport extends Model
{
    use HasFactory;

    protected $fillable = [
        'petition_id',
        'user_id',
        'comment',
    ];

    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
