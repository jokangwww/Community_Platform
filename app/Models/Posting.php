<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Posting extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'event_id',
        'description',
        'poster_path',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
