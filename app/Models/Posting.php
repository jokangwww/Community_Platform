<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PostingImage;

class Posting extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'event_id',
        'description',
        'status',
        'poster_path',
    ];

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

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'posting_favorites')
            ->withTimestamps();
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }
}
