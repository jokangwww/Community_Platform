<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostingModerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'posting_id',
        'admin_id',
        'action',
        'reason',
        'note',
        'event_name_snapshot',
        'club_name_snapshot',
    ];

    public function posting(): BelongsTo
    {
        return $this->belongsTo(Posting::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
