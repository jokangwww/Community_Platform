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
        'updated_by',
    ];

    protected $casts = [
        'semester' => 'integer',
        'total_weeks' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who last updated this setting
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
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
     * Get semester as formatted array
     */
    public function toSettingsArray(): array
    {
        return [
            'id' => $this->id,
            'academic_year' => $this->academic_year,
            'semester' => $this->semester,
            'duration_type' => $this->duration_type,
            'total_weeks' => $this->total_weeks,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'is_active' => $this->is_active,
        ];
    }
}
