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
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
