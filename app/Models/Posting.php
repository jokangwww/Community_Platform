<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PostingImage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Posting extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'event_id',
        'description',
        'status',
        'outdated_at',
        'poster_path',
    ];

    protected function casts(): array
    {
        return [
            'outdated_at' => 'datetime',
        ];
    }

    public function images()
    {
        return $this->hasMany(PostingImage::class)->orderBy('position');
    }

    public function displayImages()
    {
        $images = $this->images;
        if ($images->isEmpty() && $this->poster_path) {
            return collect([(object) ['image_path' => $this->poster_path]]);
        }

        return $images;
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(User::class, 'club_id');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'posting_favorites')
            ->withTimestamps();
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class, 'event_id', 'event_id');
    }
}
