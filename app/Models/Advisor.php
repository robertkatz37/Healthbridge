<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advisor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'advisor_manager_id', 'territory', 'license_number', 'is_active',
        'photo_path', 'phone', 'bio', 'languages', 'specialties', 'certifications',
        'working_hours', 'is_available', 'email_signature',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'languages' => 'array',
            'specialties' => 'array',
            'working_hours' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Advisor::class, 'advisor_manager_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(Advisor::class, 'advisor_manager_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AdvisorAssignment::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(AdvisorNote::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AdvisorTask::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function territories(): HasMany
    {
        return $this->hasMany(AdvisorTerritory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo_path) {
            return asset('storage/' . $this->photo_path);
        }

        $name = urlencode($this->user?->name ?? 'Advisor');
        return "https://ui-avatars.com/api/?name={$name}&background=063D2E&color=fff&size=96";
    }
}
