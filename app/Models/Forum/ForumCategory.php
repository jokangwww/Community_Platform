<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
    use HasFactory;

    protected $table = 'forum_categories';

    protected $fillable = [
        'name',
        'description',
        'type',
        'icon',
    ];

    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(ForumHashtag::class, 'forum_category_hashtag', 'category_id', 'hashtag_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'category_id');
    }

    public function getPostCountAttribute(): int
    {
        return $this->posts()->where('status', 'active')->count();
    }
}
