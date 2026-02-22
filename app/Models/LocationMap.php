<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_path',
    ];

    public function points()
    {
        return $this->hasMany(LocationPoint::class)->orderBy('name');
    }
}
