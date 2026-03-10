<?php

namespace App\Models\Forum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumPostAttachment extends Model
{
    use HasFactory;

    protected $table = 'forum_post_attachments';

    protected $fillable = [
        'post_id',
        'name',
        'path',
        'type',
        'size',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'post_id');
    }
}
