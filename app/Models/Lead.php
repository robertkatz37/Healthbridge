<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Matches the DB column defaults declared in the migration exactly.
     * Without this, Lead::create() without explicitly passing 'status'
     * leaves $lead->status NULL in-memory immediately after creation —
     * Eloquent does not reflect a DB-level default() back into the
     * in-memory model returned by create(), only a fresh reload
     * ($lead->fresh()) would see it. That gap caused a real bug: any
     * code (e.g. LeadAssignmentService::assign()) checking
     * $lead->status->value right after Lead::create() would crash with
     * "Attempt to read property on null" — caught via a failing test
     * before shipping. Declaring the same defaults here means every
     * Lead::create()/new Lead() call gets a correct in-memory value
     * immediately, matching the database, with no reliance on every call
     * site remembering to pass 'status' explicitly.
     */
    protected $attributes = [
        'status' => 'new',
        'source' => 'manual',
    ];

    protected $fillable = [
        'family_id', 'care_seeker_id', 'advisor_id', 'status', 'source',
        'territory_city', 'territory_state', 'assigned_at',
        'converted_at', 'closed_at', 'closed_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'source' => LeadSource::class,
            'assigned_at' => 'datetime',
            'converted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function careSeeker(): BelongsTo
    {
        return $this->belongsTo(CareSeeker::class);
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(LeadStatusHistory::class)->latest();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(AdvisorNote::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AdvisorTask::class);
    }

    public function tourRequests(): HasMany
    {
        return $this->hasMany(TourRequest::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function assignmentHistory(): HasMany
    {
        return $this->hasMany(AdvisorAssignment::class)->latest('assigned_at');
    }

    /**
     * A Lead's family-communication thread — reuses the polymorphic
     * conversations table (Phase 2, deferred UI until this phase) rather
     * than a lead-specific messages table.
     */
    public function conversations(): MorphMany
    {
        return $this->morphMany(Conversation::class, 'subject');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', array_map(
            fn (LeadStatus $s) => $s->value,
            LeadStatus::openStatuses()
        ));
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('advisor_id');
    }

    public function scopeForAdvisor($query, int $advisorId)
    {
        return $query->where('advisor_id', $advisorId);
    }

    public function getFamilyNameAttribute(): string
    {
        return $this->family?->user?->name ?? 'Unknown Family';
    }
}
