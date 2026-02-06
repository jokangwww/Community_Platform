<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'name',
        'description',
        'category',
        'status',
        'registration_type',
        'participant_limit',
        'start_date',
        'end_date',
        'logo_path',
        'attachment_path',
    ];

    public function committeeMembers()
    {
        return $this->belongsToMany(User::class, 'event_committees')
            ->withTimestamps();
    }

    public function subEvents()
    {
        return $this->hasMany(EventSubEvent::class)->orderBy('event_date');
    }

    public function facultyLimits()
    {
        return $this->hasMany(EventFacultyLimit::class);
    }

    public function postings()
    {
        return $this->hasMany(Posting::class);
    }

    public function ticketSetting()
    {
        return $this->hasOne(EventTicketSetting::class);
    }

    public function ticketPurchases()
    {
        return $this->hasMany(TicketPurchase::class);
    }
}
