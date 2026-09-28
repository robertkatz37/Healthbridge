<?php

namespace App\Models;

use App\Enums\CareType;
use App\Enums\MemoryStatus;
use App\Enums\MobilityLevel;
use App\Enums\MoveInTimeline;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CareSeeker extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id', 'first_name', 'last_name', 'age', 'gender',
        'medical_conditions', 'memory_status', 'mobility',
        'budget_min', 'budget_max', 'is_veteran',
        'insurance_provider', 'has_ltc_insurance', 'move_in_timeline', 'photo_path',
        'preferred_city', 'preferred_state', 'lat', 'lng', 'languages', 'care_type_needed',
        'behavioral_notes', 'adl_needs', 'emergency_contact_name', 'emergency_contact_phone',
        'internal_notes', 'religious_preference', 'wants_pet_friendly', 'uses_medicaid', 'uses_medicare',
    ];

    protected function casts(): array
    {
        return [
            'memory_status' => MemoryStatus::class,
            'mobility' => MobilityLevel::class,
            'move_in_timeline' => MoveInTimeline::class,
            'care_type_needed' => CareType::class,
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'is_veteran' => 'boolean',
            'has_ltc_insurance' => 'boolean',
            'languages' => 'array',
            'adl_needs' => 'array',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'wants_pet_friendly' => 'boolean',
            'uses_medicaid' => 'boolean',
            'uses_medicare' => 'boolean',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function needsAssessments(): HasMany
    {
        return $this->hasMany(NeedsAssessment::class);
    }

    public function latestCompletedAssessment(): ?NeedsAssessment
    {
        return $this->needsAssessments()->completed()->latest('completed_at')->first();
    }

    public function tourRequests(): HasMany
    {
        return $this->hasMany(TourRequest::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CareSeekerDocument::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(FamilyNote::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo_path) {
            return asset('storage/' . $this->photo_path);
        }

        $name = urlencode($this->full_name ?: 'Care Seeker');
        return "https://ui-avatars.com/api/?name={$name}&background=E1F5EC&color=0B6E4F&size=96";
    }

    public function scopeForFamily($query, int $familyId)
    {
        return $query->where('family_id', $familyId);
    }
}
