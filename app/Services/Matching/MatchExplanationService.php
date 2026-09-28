<?php

namespace App\Services\Matching;

/**
 * Turns a MatchingScoreService score breakdown into the human-readable
 * "Matching Reasons" / "Missing Requirements" lists and a Confidence
 * Score — the presentation layer over the raw numeric breakdown, kept
 * separate from MatchingScoreService so the scoring math and the
 * explanation wording can evolve independently.
 */
class MatchExplanationService
{
    private const STRONG_THRESHOLD = 70;
    private const WEAK_THRESHOLD = 40;

    private const FACTOR_LABELS = [
        'care_type' => 'Care Type',
        'location' => 'Location',
        'coverage_area' => 'Coverage Area',
        'distance' => 'Distance',
        'budget' => 'Budget',
        'services_offered' => 'Services Offered',
        'languages' => 'Languages Spoken',
        'insurance_accepted' => 'Insurance Accepted',
        'medicaid_medicare' => 'Medicaid/Medicare',
        'specialty_care' => 'Specialty Care',
        'memory_care' => 'Memory Care',
        'mobility' => 'Mobility Support',
        'availability' => 'Availability',
        'gender_preference' => 'Gender Preference',
        'veteran_benefits' => 'Veteran Benefits',
        'religious_preference' => 'Religious Preference',
        'pet_friendly' => 'Pet Friendly',
        'accessibility' => 'Accessibility',
        'review_rating' => 'Review Rating',
        'agency_quality' => 'Agency Quality',
        'verification_status' => 'Verification Status',
    ];

    /**
     * @return array{reasons: string[], missing: string[], confidence: float, tier: string}
     */
    public function explain(array $scoreResult): array
    {
        $reasons = [];
        $missing = [];
        $applicableCount = 0;

        foreach ($scoreResult['factors'] as $key => $factor) {
            if (!$factor['applicable']) {
                continue;
            }
            $applicableCount++;

            if ($factor['score'] >= self::STRONG_THRESHOLD) {
                $reasons[] = $factor['detail'];
            } elseif ($factor['score'] < self::WEAK_THRESHOLD) {
                $missing[] = $factor['detail'];
            }
        }

        // Confidence reflects how much of the full 21-factor picture
        // actually had data to compare — a 90% score built from only 4
        // applicable factors (most preferences left blank) is a less
        // confident recommendation than the same score built from 15.
        $totalFactors = count(self::FACTOR_LABELS);
        $confidence = $totalFactors > 0 ? round(($applicableCount / $totalFactors) * 100, 1) : 0.0;

        return [
            'reasons' => $reasons,
            'missing' => $missing,
            'confidence' => $confidence,
            'tier' => $this->tierLabel($scoreResult['total_score']),
        ];
    }

    public function tierLabel(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Excellent Match',
            $score >= 75 => 'Strong Match',
            $score >= 55 => 'Good Match',
            $score >= 35 => 'Fair Match',
            default => 'Limited Match',
        };
    }

    public function factorLabel(string $key): string
    {
        return self::FACTOR_LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}
