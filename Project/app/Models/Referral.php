<?php

namespace App\Models;

use App\Enums\ReferralPriority;
use App\Enums\ReferralSource;
use App\Enums\ReferralStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Referral extends Model
{
    use HasFactory;

    /**
     * Matches the DB column defaults exactly. Without this,
     * Referral::create() without explicitly passing 'status'/'priority'
     * leaves them NULL in-memory immediately after creation — this
     * exact class of bug has now been caught and fixed five times across
     * this project (Setting, Lead, Agency, AdvisorTask, and now here
     * proactively) — see DATABASE_DECISIONS.md.
     */
    protected $attributes = [
        'status' => 'pending',
        'priority' => 'medium',
    ];

    protected $fillable = [
        'uuid', 'family_id', 'care_seeker_id', 'agency_id', 'advisor_id', 'lead_id',
        'status', 'priority', 'source', 'sent_at', 'agency_responded_at',
        'converted_at', 'closed_at', 'closed_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReferralStatus::class,
            'priority' => ReferralPriority::class,
            'source' => ReferralSource::class,
            'sent_at' => 'datetime',
            'agency_responded_at' => 'datetime',
            'converted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Referral $referral) {
            $referral->uuid ??= (string) Str::uuid();
        });
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function careSeeker(): BelongsTo
    {
        return $this->belongsTo(CareSeeker::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ReferralStatusHistory::class)->latest();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ReferralNote::class)->latest();
    }

    public function tourRequests(): HasMany
    {
        return $this->hasMany(TourRequest::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }

    public function scopeStatus($query, ReferralStatus|string $status)
    {
        return $query->where('status', $status instanceof ReferralStatus ? $status->value : $status);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', array_map(fn ($s) => $s->value, ReferralStatus::openStatuses()));
    }

    public function getFamilyNameAttribute(): string
    {
        return $this->family?->user?->name ?? 'Unknown Family';
    }
}
