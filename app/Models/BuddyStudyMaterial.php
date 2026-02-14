<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddyStudyMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'uploaded_by',
        'name',
        'description',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    /**
     * Get the match this material belongs to
     */
    public function match()
    {
        return $this->belongsTo(BuddyMatch::class, 'match_id');
    }

    /**
     * Get the participant who uploaded this material
     */
    public function uploader()
    {
        return $this->belongsTo(BuddyParticipant::class, 'uploaded_by');
    }
}
