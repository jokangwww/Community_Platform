<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'student_id',
        'order_id',
        'capture_id',
        'amount',
        'currency',
        'ticket_number',
        'ticket_number_seq',
        'status',
        'is_resale_listed',
        'resale_price',
        'resale_listed_at',
        'last_transferred_at',
        'early_bird_applied',
        'early_bird_discount_percent',
        'bundle_discount_percent',
        'base_unit_amount',
        'attended_at',
        'attendance_marked_by',
    ];

    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
            'is_resale_listed' => 'boolean',
            'resale_price' => 'decimal:2',
            'resale_listed_at' => 'datetime',
            'last_transferred_at' => 'datetime',
            'early_bird_applied' => 'boolean',
            'early_bird_discount_percent' => 'decimal:2',
            'bundle_discount_percent' => 'decimal:2',
            'base_unit_amount' => 'decimal:2',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function attendanceMarker()
    {
        return $this->belongsTo(User::class, 'attendance_marked_by');
    }
}
