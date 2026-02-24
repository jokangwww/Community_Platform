<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuddySetting extends Model
{
    protected $fillable = [
        'priority_allocation',
        'registration_open',
        'evaluation_enabled',
        'testimonial_enabled',
        'updated_by',
    ];

    protected $casts = [
        'priority_allocation' => 'boolean',
        'registration_open' => 'boolean',
        'evaluation_enabled' => 'boolean',
        'testimonial_enabled' => 'boolean',
    ];

    /**
     * Get the user who last updated this setting
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the settings instance (singleton pattern - always one row)
     */
    public static function getInstance(): self
    {
        $settings = self::first();
        
        if (!$settings) {
            $settings = self::create([
                'priority_allocation' => true,
                'registration_open' => true,
                'evaluation_enabled' => false,
                'testimonial_enabled' => false,
            ]);
        }
        
        return $settings;
    }

    /**
     * Get all settings as array
     */
    public static function getAllSettings(): array
    {
        $settings = self::getInstance();
        
        return [
            'priority_allocation_enabled' => $settings->priority_allocation,
            'registration_open' => $settings->registration_open,
            'evaluation_enabled' => $settings->evaluation_enabled,
            'testimonial_enabled' => $settings->testimonial_enabled,
        ];
    }

    /**
     * Update a setting by key
     */
    public static function setValue(string $key, mixed $value, ?int $updatedBy = null): bool
    {
        $settings = self::getInstance();
        
        // Map API keys to database columns
        $columnMap = [
            'priority_allocation_enabled' => 'priority_allocation',
            'registration_open' => 'registration_open',
            'evaluation_enabled' => 'evaluation_enabled',
            'testimonial_enabled' => 'testimonial_enabled',
        ];
        
        $column = $columnMap[$key] ?? null;
        
        if (!$column) {
            return false;
        }
        
        return $settings->update([
            $column => (bool) $value,
            'updated_by' => $updatedBy,
        ]);
    }

    /**
     * Check if priority allocation is enabled
     */
    public static function isPriorityAllocationEnabled(): bool
    {
        return self::getInstance()->priority_allocation;
    }

}
