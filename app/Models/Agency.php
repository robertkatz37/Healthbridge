<?php

namespace App\Models;

use App\Enums\AgencyStatus;
use App\Enums\GenderServed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agency extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Matches the DB column defaults declared in the Phase 12 migration
     * exactly. Without this, Agency::create()/factory()->create() without
     * explicitly passing these keys leaves them NULL in-memory
     * immediately after creation — Eloquent does not reflect a DB-level
     * default() back into the in-memory model returned by create(), only
     * a fresh reload would see it. This is the same gap caught and fixed
     * on Lead (Phase 11) and AdvisorTask (Phase 11 completion pass); see
     * DATABASE_DECISIONS.md. Caught here via smoke testing before
     * shipping: a freshly-factory-created Agency scored as "not
     * available" and effectively genderless-mismatch-prone despite the
     * database correctly defaulting has_availability=true and
     * gender_served='any'.
     */
    protected $attributes = [
        'has_availability' => true,
        'gender_served' => 'any',
        'accepts_medicaid' => false,
        'accepts_medicare' => false,
        'is_pet_friendly' => false,
        'is_wheelchair_accessible' => false,
        'is_veteran_friendly' => false,
    ];

    protected $fillable = [
        'user_id',
        'agency_category_id',
        'name',
        'slug',
        'description',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'lat',
        'lng',
        'status',
        'onboarding_step',
        'onboarding_completed_at',
        'is_featured',
        'featured_until',
        'review_score',
        'min_monthly_cost',
        'max_monthly_cost',
        'languages',
        'accepted_insurance_providers',
        'accepts_medicaid',
        'accepts_medicare',
        'is_pet_friendly',
        'is_wheelchair_accessible',
        'is_veteran_friendly',
        'religious_affiliation',
        'gender_served',
        'has_availability',
    ];

    protected function casts(): array
    {
        return [
            'status' => AgencyStatus::class,
            'onboarding_completed_at' => 'datetime',
            'is_featured' => 'boolean',
            'featured_until' => 'datetime',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'review_score' => 'decimal:2',
            'min_monthly_cost' => 'decimal:2',
            'max_monthly_cost' => 'decimal:2',
            'languages' => 'array',
            'accepted_insurance_providers' => 'array',
            'accepts_medicaid' => 'boolean',
            'accepts_medicare' => 'boolean',
            'is_pet_friendly' => 'boolean',
            'is_wheelchair_accessible' => 'boolean',
            'is_veteran_friendly' => 'boolean',
            'gender_served' => GenderServed::class,
            'has_availability' => 'boolean',
        ];
    }

    public function isOnboardingComplete(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    /**
     * Agency Owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AgencyCategory::class, 'agency_category_id');
    }

    /**
     * Agency Services
     */
    public function services(): HasMany
    {
        return $this->hasMany(AgencyService::class);
    }

    /**
     * Business Hours
     */
    public function hours(): HasMany
    {
        return $this->hasMany(AgencyHour::class);
    }

    /**
     * Coverage Areas
     */
    public function coverage(): HasMany
    {
        return $this->hasMany(AgencyCoverage::class);
    }

    /**
     * Certifications
     */
    public function certifications(): HasMany
    {
        return $this->hasMany(AgencyCertification::class);
    }

    /**
     * Media Files
     */
    public function media(): HasMany
    {
        return $this->hasMany(AgencyMedia::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(AgencyStaff::class);
    }

    public function pricing(): HasMany
    {
        return $this->hasMany(AgencyPricing::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AgencyDocument::class);
    }

    public function favoritedBy(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function tourRequests(): HasMany
    {
        return $this->hasMany(TourRequest::class);
    }

    public function matchResults(): HasMany
    {
        return $this->hasMany(MatchResult::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function commissionRules(): HasMany
    {
        return $this->hasMany(CommissionRule::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(Award::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(AgencyStatusHistory::class)->latest();
    }

    public function adminNotes(): HasMany
    {
        return $this->hasMany(AgencyAdminNote::class)->latest();
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function defaultPaymentMethod(): HasOne
    {
        return $this->hasOne(PaymentMethod::class)->where('is_default', true);
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seo_metable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', AgencyStatus::Published->value);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('has_availability', true);
    }

    public function scopeInCity($query, string $city, string $state)
    {
        return $query->where('city', $city)->where('state', $state);
    }

    public function scopeInModerationQueue($query)
    {
        return $query->whereIn('status', array_map(
            fn (AgencyStatus $s) => $s->value,
            AgencyStatus::queueStatuses()
        ));
    }
}
