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
        'prefix',
        'suffix',
        'start_number',
        'number_padding',
        'last_number',
    ];

    protected $casts = [
        'bundle_discounts' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
