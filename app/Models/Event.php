<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'name',
        'description',
        'venue',
        'booth_locations',
        'status',
        'approval_status',
        'rejection_reason',
        'registration_type',
        'participant_limit',
        'start_date',
        'end_date',
        'logo_path',
        'attachment_path',
        'live_stream_url',
        'live_stream_started_at',
        'live_stream_stop_reason',
        'live_stream_stopped_at',
        'live_stream_stopped_by_admin_id',
        'soft_skill_category_id',
    ];

    protected function casts(): array
    {
        return [
            'live_stream_started_at' => 'datetime',
            'live_stream_stopped_at' => 'datetime',
        ];
    }

    public function committeeMembers()
    {
        return $this->belongsToMany(User::class, 'event_committees')
            ->withPivot('position_name', 'attended_at', 'attendance_marked_by')
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

    public function club(): BelongsTo
    {
        return $this->belongsTo(User::class, 'club_id');
    }

    public function ticketSetting()
    {
        return $this->hasOne(EventTicketSetting::class);
    }

    public function ticketPurchases()
    {
        return $this->hasMany(TicketPurchase::class);
    }

    public function calendarEntries()
    {
        return $this->hasMany(StudentCalendarEvent::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(EventFeedback::class);
    }

    public function luckyDraw(): HasOne
    {
        return $this->hasOne(LuckyDraw::class);
    }

    public function softSkillSetting(): HasOne
    {
        return $this->hasOne(EventSoftSkillSetting::class);
    }

    public function streamViewers(): HasMany
    {
        return $this->hasMany(EventStreamViewer::class);
    }

    public function boothPlaces(): HasMany
    {
        return $this->hasMany(EventBoothPlace::class)->orderBy('id');
    }

    public function softSkillCategory(): BelongsTo
    {
        return $this->belongsTo(SoftSkillCategory::class, 'soft_skill_category_id');
    }

    public function getLiveStreamEmbedUrlAttribute(): ?string
    {
        $url = trim((string) $this->live_stream_url);
        if ($url === '') {
            return null;
        }

        // Convert common YouTube URL formats into embeddable URLs.
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $matches) === 1) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        if (Str::contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        return $url;
    }

    public function activeStreamViewerCount(int $windowSeconds = 120): int
    {
        return $this->streamViewers()
            ->where('last_seen_at', '>=', now()->subSeconds($windowSeconds))
            ->count();
    }

    public function boothLocationOptions(): array
    {
        $raw = (string) ($this->booth_locations ?? '');
        if (trim($raw) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $cleaned = array_map(fn ($line) => trim((string) $line), $lines);
        $filled = array_values(array_filter($cleaned, fn ($line) => $line !== ''));

        return array_values(array_unique($filled));
    }
}
