<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuddySubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get participants with this subject (direct relationship)
     */
    public function directParticipants()
    {
        return $this->hasMany(BuddyParticipant::class, 'subject_id');
    }

    /**
     * Get all matches for this subject
     */
    public function matches()
    {
        return $this->hasMany(BuddyMatch::class, 'subject_id');
    }

    /**
     * Scope for active subjects
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for subjects only
     */
    public function scopeSubjects($query)
    {
        return $query->where('type', 'subject');
    }

    /**
     * Scope for skills only
     */
    public function scopeSkills($query)
    {
        return $query->where('type', 'skill');
    }

    /**
     * Search by code or name
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%");
        });
    }

    /**
     * Get display name (code + name for subjects, just name for skills)
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'subject' && $this->code) {
            return "{$this->code} - {$this->name}";
        }
        return $this->name;
    }
}
