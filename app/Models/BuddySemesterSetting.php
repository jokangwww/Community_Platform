<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuddySemesterSetting extends Model
{
    protected $fillable = [
        'academic_year',
        'semester',
        'duration_type',
        'total_weeks',
        'start_date',
        'end_date',
        'is_active',
        'registration_open',
        'evaluation_enabled',
        'testimonial_enabled',
        'priority_allocation',
        'updated_by',
    ];

    protected $casts = [
        'semester'             => 'integer',
        'total_weeks'          => 'integer',
        'start_date'           => 'date',
        'end_date'             => 'date',
        'is_active'            => 'boolean',
        'registration_open'    => 'boolean',
        'evaluation_enabled'   => 'boolean',
        'testimonial_enabled'  => 'boolean',
        'priority_allocation'  => 'boolean',
    ];

    /**
     * The user who last updated this setting
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Participants registered in this semester
     */
    public function participants()
    {
        return $this->hasMany(BuddyParticipant::class, 'semester_id');
    }

    /**
     * Matches created in this semester
     */
    public function matches()
    {
        return $this->hasMany(BuddyMatch::class, 'semester_id');
    }

    /**
     * Get the current active semester setting
     */
    public static function getActiveSemester(): ?self
    {
        return self::where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->first();
    }

    /**
     * Get all semesters ordered by start date (newest first)
     */
    public static function getAllSemesters()
    {
        return self::orderBy('start_date', 'desc')->get();
    }

    /**
     * Whether this semester has ended (end_date in the past and not active)
     */
    public function isEnded(): bool
    {
        return !$this->is_active && $this->end_date->isPast();
    }

    /**
     * Get label like "Semester 2, 2024/2025"
     */
    public function getLabel(): string
    {
        return "Semester {$this->semester}, {$this->academic_year}";
    }

    /**
     * Get semester as formatted array
     */
    public function toSettingsArray(): array
    {
        return [
            'id'                  => $this->id,
            'academic_year'       => $this->academic_year,
            'semester'            => $this->semester,
            'duration_type'       => $this->duration_type,
            'total_weeks'         => $this->total_weeks,
            'start_date'          => $this->start_date->format('Y-m-d'),
            'end_date'            => $this->end_date->format('Y-m-d'),
            'is_active'           => $this->is_active,
            'registration_open'   => $this->registration_open,
            'evaluation_enabled'  => $this->evaluation_enabled,
            'testimonial_enabled' => $this->testimonial_enabled,
            'priority_allocation' => $this->priority_allocation,
            'label'               => $this->getLabel(),
            'is_ended'            => $this->isEnded(),
        ];
    }
}
