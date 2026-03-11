<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventTicketSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'price',
        'currency',
        'bundle_discounts',
        'early_bird_enabled',
        'early_bird_start_at',
        'early_bird_end_at',
        'early_bird_discount_percent',
        'early_bird_faculties',
        'early_bird_study_years',
        'early_bird_roles',
        'prefix',
        'suffix',
        'start_number',
        'number_padding',
        'last_number',
    ];

    protected $casts = [
        'bundle_discounts' => 'array',
        'early_bird_enabled' => 'boolean',
        'early_bird_start_at' => 'datetime',
        'early_bird_end_at' => 'datetime',
        'early_bird_faculties' => 'array',
        'early_bird_study_years' => 'array',
        'early_bird_roles' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
