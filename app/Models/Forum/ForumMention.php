<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ForumMention extends Model
{
    use HasFactory;

    protected $table = 'forum_mentions';

    protected $fillable = [
        'user_id',
        'mentionable_id',
        'mentionable_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentionable(): MorphTo
    {
        return $this->morphTo();
    }
}
