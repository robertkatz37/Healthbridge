<?php

namespace App\Services\Agency;

use App\Models\Agency;

/**
 * Computes dashboard KPIs for an Agency. Referral/review volume will be
 * zero for most agencies until the Matching Engine (Phase 10) and Referral
 * Engine (Phase 12) are live — the queries are written against the real
 * schema now so numbers appear automatically once those phases ship,
 * rather than needing a rewrite later.
 */
class AgencyAnalyticsService
{
    public function summary(Agency $agency): array
    {
        return [
            'total_leads' => $agency->referrals()->count(),
            'pending_leads' => $agency->referrals()->where('status', 'pending')->count(),
            'converted_leads' => $agency->referrals()->where('status', 'converted')->count(),
            'total_reviews' => $agency->reviews()->where('status', 'published')->count(),
            'average_rating' => $agency->review_score,
            'profile_completeness' => $this->profileCompleteness($agency),
        ];
    }

    /**
     * Simple weighted completeness score across the sections a family
     * would want to see before trusting a listing: basics, services,
     * hours, coverage, media, certifications.
     */
    public function profileCompleteness(Agency $agency): int
    {
        // NOTE: this must be a list of [passed, weight] pairs, not an
        // associative array keyed by the boolean itself — PHP casts bool
        // array keys to 0/1, silently collapsing every "false" entry into
        // one key and every "true" entry into another, discarding all but
        // the last value written to each. Caught via end-to-end smoke
        // testing (see Phase 7 notes) before this shipped.
        $checks = [
            [(bool) $agency->description, 15],
            [(bool) $agency->phone, 10],
            [(bool) $agency->address, 10],
            [$agency->services()->count() > 0, 20],
            [$agency->hours()->count() > 0, 15],
            [$agency->coverage()->count() > 0, 15],
            [$agency->media()->count() > 0, 15],
        ];

        $score = 0;
        foreach ($checks as [$passed, $weight]) {
            if ($passed) {
                $score += $weight;
            }
        }

        return min($score, 100);
    }
}
