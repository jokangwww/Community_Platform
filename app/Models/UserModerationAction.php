<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserModerationAction extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'report_id',
        'action',
        'note',
        'content_type',
        'content_id',
        'mute_duration_days',
    ];

    protected function casts(): array
    {
        return [
            'mute_duration_days' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
