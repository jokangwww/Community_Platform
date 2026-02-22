<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_map_id',
        'name',
        'description',
        'x_percent',
        'y_percent',
    ];

    public function map()
    {
        return $this->belongsTo(LocationMap::class, 'location_map_id');
    }
}
