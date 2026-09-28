<?php

namespace App\Models;

use App\Enums\TourRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id', 'care_seeker_id', 'agency_id', 'lead_id', 'referral_id',
        'requested_date', 'requested_time_window', 'status', 'notes',
        'confirmed_at', 'completed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'status' => TourRequestStatus::class,
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('requested_date', '>=', now()->toDateString());
    }
}
