<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBoothApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'event_id',
        'vendor_name_snapshot',
        'vendor_email_snapshot',
        'vendor_phone_snapshot',
        'items_for_sale',
        'status',
        'organizer_reviewed_by',
        'organizer_review_reason',
        'organizer_reviewed_at',
        'admin_reviewed_by',
        'admin_review_reason',
        'admin_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'organizer_reviewed_at' => 'datetime',
            'admin_reviewed_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function organizerReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_reviewed_by');
    }

    public function adminReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_reviewed_by');
    }
}

