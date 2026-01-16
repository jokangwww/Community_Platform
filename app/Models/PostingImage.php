<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostingImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'posting_id',
        'image_path',
        'position',
    ];

    public function posting()
    {
        return $this->belongsTo(Posting::class);
    }
}
