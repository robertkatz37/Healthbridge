<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NeedsAssessment extends Model
{
    use HasFactory;

    protected $fillable = ['care_seeker_id', 'status', 'current_step', 'score_profile', 'completed_at', 'last_matched_at'];

    protected function casts(): array
    {
        return [
            'score_profile' => 'array',
            'completed_at' => 'datetime',
            'last_matched_at' => 'datetime',
        ];
    }

    public function careSeeker(): BelongsTo
    {
        return $this->belongsTo(CareSeeker::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(NeedsAssessmentAnswer::class);
    }

    public function matchResults(): HasMany
    {
        return $this->hasMany(MatchResult::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
}
