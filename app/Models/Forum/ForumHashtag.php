<?php

namespace App\Models\Forum;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ForumHashtag extends Model
{
    use HasFactory;

    protected $table = 'forum_hashtags';

    protected $fillable = [
        'name',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ForumCategory::class, 'forum_category_hashtag', 'hashtag_id', 'category_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(ForumPost::class, 'forum_post_hashtag', 'hashtag_id', 'post_id');
    }
}
