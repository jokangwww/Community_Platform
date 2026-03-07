<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'student_id',
        'staff_id',
        'study_year',
        'department',
        'position',
        'contact_information',
        'responsibilities',
        'email',
        'password',
        'profile_photo_path',
        'display_name',
        'nickname',
        'role',
        'club_attachment_path',
        'club_approval_status',
        'club_approved_at',
        'account_status',
        'ban_reason',
        'banned_at',
        'appeal_status',
        'appeal_message',
        'appeal_review_note',
        'appealed_at',
        'appeal_reviewed_at',
        'bio',
        'muted_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'club_approved_at' => 'datetime',
            'banned_at' => 'datetime',
            'appealed_at' => 'datetime',
            'appeal_reviewed_at' => 'datetime',
            'muted_until' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function favoritePostings(): BelongsToMany
    {
        return $this->belongsToMany(Posting::class, 'posting_favorites')
            ->withTimestamps();
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(StudentCalendarEvent::class, 'student_id');
    }

    public function postings(): HasMany
    {
        return $this->hasMany(Posting::class, 'club_id');
    }

    public function eventsOrganized(): HasMany
    {
        return $this->hasMany(Event::class, 'club_id');
    }
}
