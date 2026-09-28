<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'needs_assessment_id', 'agency_id', 'compatibility_score',
        'score_breakdown', 'is_advisor_approved', 'sort_order',
        'is_family_shortlisted', 'is_hidden_by_advisor', 'advisor_override_note',
    ];

    protected function casts(): array
    {
        return [
            'compatibility_score' => 'decimal:2',
            'score_breakdown' => 'array',
            'is_advisor_approved' => 'boolean',
            'is_family_shortlisted' => 'boolean',
            'is_hidden_by_advisor' => 'boolean',
        ];
    }

    public function needsAssessment(): BelongsTo
    {
        return $this->belongsTo(NeedsAssessment::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_advisor_approved', true);
    }

    public function scopeShortlisted($query)
    {
        return $query->where('is_family_shortlisted', true);
    }

    /**
     * What a family should actually see — excludes anything an advisor
     * has deliberately hidden without touching the underlying score.
     */
    public function scopeVisibleToFamily($query)
    {
        return $query->where('is_hidden_by_advisor', false);
    }

    public function scopeRanked($query)
    {
        return $query->orderByDesc('compatibility_score');
    }
}
