<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetitionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'petition_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
