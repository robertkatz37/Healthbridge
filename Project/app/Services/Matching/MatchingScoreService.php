<?php

namespace App\Services\Matching;

use App\Models\Agency;
use App\Models\CareSeeker;

/**
 * Computes a per-factor score breakdown (each 0-100) for one Care Seeker
 * / Agency pair, then combines them into a single weighted 0-100 total.
 *
 * Each factor scorer returns ['score' => 0-100|null, 'applicable' =>
 * bool, 'detail' => string]. `applicable=false` means neither side had
 * enough data to compare (e.g. the family never specified a religious
 * preference) — those factors are excluded from the weighted average
 * entirely rather than counted as a failure, so an unanswered preference
 * never silently drags down a score the way a 0 would. This is why the
 * final normalization divides by the sum of weights for *applicable*
 * factors only, not all configured weights.
 */
class MatchingScoreService
{
    public function __construct(
        private readonly MatchingRuleEngine $rules,
        private readonly DistanceCalculatorService $distance,
        private readonly CityCoordinateResolver $cityResolver,
    ) {}

    public function score(CareSeeker $careSeeker, Agency $agency): array
    {
        $factors = [
            'care_type' => $this->scoreCareType($careSeeker, $agency),
            'location' => $this->scoreLocation($careSeeker, $agency),
            'coverage_area' => $this->scoreCoverageArea($careSeeker, $agency),
            'distance' => $this->scoreDistance($careSeeker, $agency),
            'budget' => $this->scoreBudget($careSeeker, $agency),
            'services_offered' => $this->scoreServicesOffered($careSeeker, $agency),
            'languages' => $this->scoreLanguages($careSeeker, $agency),
            'insurance_accepted' => $this->scoreInsurance($careSeeker, $agency),
            'medicaid_medicare' => $this->scoreMedicaidMedicare($careSeeker, $agency),
            'specialty_care' => $this->scoreSpecialtyCare($agency),
            'memory_care' => $this->scoreMemoryCare($careSeeker, $agency),
            'mobility' => $this->scoreMobility($careSeeker, $agency),
            'availability' => $this->scoreAvailability($agency),
            'gender_preference' => $this->scoreGenderPreference($careSeeker, $agency),
            'veteran_benefits' => $this->scoreVeteranBenefits($careSeeker, $agency),
            'religious_preference' => $this->scoreReligiousPreference($careSeeker, $agency),
            'pet_friendly' => $this->scorePetFriendly($careSeeker, $agency),
            'accessibility' => $this->scoreAccessibility($careSeeker, $agency),
            'review_rating' => $this->scoreReviewRating($agency),
            'agency_quality' => $this->scoreAgencyQuality($agency),
            'verification_status' => $this->scoreVerificationStatus($agency),
        ];

        $weights = $this->rules->weights();
        $weightedSum = 0.0;
        $totalApplicableWeight = 0.0;

        foreach ($factors as $key => $factor) {
            if (!$factor['applicable']) {
                continue;
            }
            $weight = (float) ($weights[$key] ?? 0);
            $weightedSum += $factor['score'] * $weight;
            $totalApplicableWeight += $weight;
        }

        $baseScore = $totalApplicableWeight > 0 ? ($weightedSum / $totalApplicableWeight) : 0.0;

        $featuredBoost = $agency->is_featured ? $this->rules->featuredBoost() : 0.0;
        $totalScore = min(100, round($baseScore + $featuredBoost, 2));

        return [
            'total_score' => $totalScore,
            'base_score' => round($baseScore, 2),
            'featured_boost_applied' => $featuredBoost,
            'factors' => $factors,
            'distance_miles' => $factors['distance']['miles'] ?? null,
        ];
    }

    private function scoreCareType(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->care_type_needed) {
            return ['score' => null, 'applicable' => false, 'detail' => 'No care type specified'];
        }

        $matches = $agency->category?->code === $careSeeker->care_type_needed->value;

        return [
            'score' => $matches ? 100 : 25,
            'applicable' => true,
            'detail' => $matches
                ? 'Provides ' . $careSeeker->care_type_needed->label()
                : 'Primary care type is ' . ($agency->category?->name ?? 'unspecified'),
        ];
    }

    private function scoreLocation(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->preferred_state) {
            return ['score' => null, 'applicable' => false, 'detail' => 'No preferred location specified'];
        }

        $tier = $this->distance->locationTier(
            $careSeeker->preferred_city, $careSeeker->preferred_state,
            $agency->city, $agency->state
        );

        return [
            'score' => $tier['score'],
            'applicable' => true,
            'detail' => match ($tier['tier']) {
                'same_city' => 'Located in ' . $agency->city . ', ' . $agency->state,
                'same_state' => 'Same state (' . $agency->state . ')',
                default => $agency->city . ', ' . $agency->state,
            },
        ];
    }

    private function scoreCoverageArea(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->preferred_city && !$careSeeker->preferred_state) {
            return ['score' => null, 'applicable' => false, 'detail' => 'No preferred location specified'];
        }

        $coverage = $agency->coverage;

        $exactMatch = $coverage->contains(fn ($c) => mb_strtolower($c->city ?? '') === mb_strtolower($careSeeker->preferred_city ?? '')
            && mb_strtolower($c->state ?? '') === mb_strtolower($careSeeker->preferred_state ?? ''));
        if ($exactMatch) {
            return ['score' => 100, 'applicable' => true, 'detail' => 'Explicitly covers ' . $careSeeker->preferred_city];
        }

        $stateMatch = $coverage->contains(fn ($c) => mb_strtolower($c->state ?? '') === mb_strtolower($careSeeker->preferred_state ?? ''));
        if ($stateMatch) {
            return ['score' => 50, 'applicable' => true, 'detail' => 'Covers ' . $careSeeker->preferred_state . ' region'];
        }

        if ($coverage->isEmpty()) {
            return ['score' => 30, 'applicable' => true, 'detail' => 'No specific coverage area declared'];
        }

        return ['score' => 10, 'applicable' => true, 'detail' => 'Coverage area does not include your region'];
    }

    private function scoreDistance(CareSeeker $careSeeker, Agency $agency): array
    {
        $careSeekerCoords = $this->resolveCoords($careSeeker->lat, $careSeeker->lng, $careSeeker->preferred_city, $careSeeker->preferred_state);
        $agencyCoords = $this->resolveCoords($agency->lat, $agency->lng, $agency->city, $agency->state);

        if (!$careSeekerCoords || !$agencyCoords) {
            // Neither explicit coordinates nor a resolvable city/state on
            // at least one side — genuinely nothing to compute from.
            return ['score' => null, 'applicable' => false, 'miles' => null, 'detail' => 'Location unavailable'];
        }

        $miles = $this->distance->milesBetween(
            $careSeekerCoords['lat'], $careSeekerCoords['lng'], $agencyCoords['lat'], $agencyCoords['lng'],
        );

        $maxDistance = $this->rules->maxDistanceMiles();
        $score = max(0, 100 - ($miles / max($maxDistance, 1)) * 100);

        // Both sides resolved from an exact address (lat/lng on file, not
        // a city/state lookup) — an honest "approx." qualifier is shown
        // otherwise, since a state-centroid fallback in particular can be
        // off by 100+ miles for a large state.
        $isExact = $careSeekerCoords['exact'] && $agencyCoords['exact'];
        $detail = $isExact ? $miles . ' miles away' : 'approx. ' . $miles . ' miles away';

        return ['score' => round($score, 1), 'applicable' => true, 'miles' => $miles, 'detail' => $detail];
    }

    /**
     * @return array{lat: float, lng: float, exact: bool}|null exact=true
     *  only when real lat/lng columns were set (not resolved from a
     *  city/state lookup).
     */
    private function resolveCoords(mixed $lat, mixed $lng, ?string $city, ?string $state): ?array
    {
        if ($lat !== null && $lng !== null) {
            return ['lat' => (float) $lat, 'lng' => (float) $lng, 'exact' => true];
        }

        $resolved = $this->cityResolver->resolve($city, $state);
        if (!$resolved) {
            return null;
        }

        // A resolved value is never "exact" even when it came from the
        // city-level table (not just the state centroid) — it's still a
        // city-center approximation, not the agency's/family's actual
        // address. Only real lat/lng on file counts as exact.
        return ['lat' => $resolved['lat'], 'lng' => $resolved['lng'], 'exact' => false];
    }

    private function scoreBudget(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->budget_min && !$careSeeker->budget_max) {
            return ['score' => null, 'applicable' => false, 'detail' => 'No budget specified'];
        }
        if (!$agency->min_monthly_cost && !$agency->max_monthly_cost) {
            return ['score' => null, 'applicable' => false, 'detail' => 'Agency has not published pricing'];
        }

        $familyMin = (float) ($careSeeker->budget_min ?? 0);
        $familyMax = (float) ($careSeeker->budget_max ?? PHP_FLOAT_MAX);
        $agencyMin = (float) ($agency->min_monthly_cost ?? 0);
        $agencyMax = (float) ($agency->max_monthly_cost ?? $agencyMin);

        // No overlap at all between the two ranges.
        if ($agencyMin > $familyMax || $agencyMax < $familyMin) {
            $gap = $agencyMin > $familyMax ? $agencyMin - $familyMax : $familyMin - $agencyMax;
            $overBudgetPct = $familyMax > 0 ? min(1, $gap / $familyMax) : 1;

            return [
                'score' => round(max(0, 30 * (1 - $overBudgetPct)), 1),
                'applicable' => true,
                'detail' => 'Starting at $' . number_format($agencyMin) . '/mo, outside your budget',
            ];
        }

        return ['score' => 100, 'applicable' => true, 'detail' => 'Within your $' . number_format($familyMin) . '–$' . number_format($familyMax === PHP_FLOAT_MAX ? $agencyMax : $familyMax) . ' budget'];
    }

    private function scoreServicesOffered(CareSeeker $careSeeker, Agency $agency): array
    {
        $adlNeeds = $careSeeker->adl_needs ?? [];
        if (empty($adlNeeds)) {
            return ['score' => null, 'applicable' => false, 'detail' => 'No specific care needs on file'];
        }

        $serviceNames = $agency->services->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();

        // Maps ADL need keywords to service-catalog keywords likely to
        // cover them — a light heuristic, not a rigid taxonomy, since ADL
        // categories and service catalog names were designed
        // independently (Phase 2/7 vs Phase 9).
        $adlServiceMap = [
            'bathing' => ['personal care'],
            'dressing' => ['personal care'],
            'toileting' => ['personal care'],
            'transferring' => ['personal care', 'physical therapy'],
            'eating' => ['meal preparation', 'personal care'],
            'continence' => ['personal care'],
            'medication_management' => ['medication management'],
        ];

        $covered = 0;
        foreach ($adlNeeds as $need) {
            $keywords = $adlServiceMap[$need] ?? [];
            foreach ($keywords as $keyword) {
                if (collect($serviceNames)->contains(fn ($s) => str_contains($s, $keyword))) {
                    $covered++;
                    break;
                }
            }
        }

        $pct = count($adlNeeds) > 0 ? ($covered / count($adlNeeds)) * 100 : 0;

        return ['score' => round($pct, 1), 'applicable' => true, 'detail' => "Covers {$covered} of " . count($adlNeeds) . ' daily living needs'];
    }

    private function scoreLanguages(CareSeeker $careSeeker, Agency $agency): array
    {
        $familyLanguages = collect($careSeeker->languages ?? [])->map(fn ($l) => mb_strtolower($l));
        if ($familyLanguages->isEmpty()) {
            return ['score' => null, 'applicable' => false, 'detail' => 'No language preference specified'];
        }

        $agencyLanguages = collect($agency->languages ?? [])->map(fn ($l) => mb_strtolower($l));
        if ($agencyLanguages->isEmpty()) {
            return ['score' => 40, 'applicable' => true, 'detail' => 'Language capabilities not listed'];
        }

        $overlap = $familyLanguages->intersect($agencyLanguages)->count();
        $pct = ($overlap / $familyLanguages->count()) * 100;

        return ['score' => round($pct, 1), 'applicable' => true, 'detail' => $overlap > 0 ? 'Speaks ' . implode(', ', $familyLanguages->intersect($agencyLanguages)->all()) : 'No matching language staff listed'];
    }

    private function scoreInsurance(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->insurance_provider) {
            return ['score' => null, 'applicable' => false, 'detail' => 'No insurance provider on file'];
        }

        $accepted = collect($agency->accepted_insurance_providers ?? []);
        if ($accepted->isEmpty()) {
            return ['score' => 40, 'applicable' => true, 'detail' => 'Accepted insurance providers not listed'];
        }

        $matches = $accepted->contains(fn ($p) => str_contains(mb_strtolower($p), mb_strtolower($careSeeker->insurance_provider))
            || str_contains(mb_strtolower($careSeeker->insurance_provider), mb_strtolower($p)));

        return [
            'score' => $matches ? 100 : 20,
            'applicable' => true,
            'detail' => $matches ? 'Accepts ' . $careSeeker->insurance_provider : 'Does not list ' . $careSeeker->insurance_provider,
        ];
    }

    private function scoreMedicaidMedicare(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->uses_medicaid && !$careSeeker->uses_medicare) {
            return ['score' => null, 'applicable' => false, 'detail' => 'Not applicable'];
        }

        $needed = array_filter([
            $careSeeker->uses_medicaid ? 'Medicaid' : null,
            $careSeeker->uses_medicare ? 'Medicare' : null,
        ]);
        $accepted = array_filter([
            $careSeeker->uses_medicaid && $agency->accepts_medicaid,
            $careSeeker->uses_medicare && $agency->accepts_medicare,
        ]);

        $pct = count($needed) > 0 ? (count($accepted) / count($needed)) * 100 : 0;

        return ['score' => round($pct, 1), 'applicable' => true, 'detail' => count($accepted) > 0 ? 'Accepts ' . implode(' & ', $needed) : 'Does not accept ' . implode('/', $needed)];
    }

    private function scoreSpecialtyCare(Agency $agency): array
    {
        $count = $agency->certifications->count();

        return [
            'score' => $count > 0 ? min(100, 60 + $count * 10) : 40,
            'applicable' => true,
            'detail' => $count > 0 ? $count . ' specialty ' . str('certification')->plural($count) . ' on file' : 'No specialty certifications on file',
        ];
    }

    private function scoreMemoryCare(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->memory_status || $careSeeker->memory_status->value === 'none') {
            return ['score' => null, 'applicable' => false, 'detail' => 'No memory care needs indicated'];
        }

        $isMemoryCategory = $agency->category?->code === 'memory_care';
        $hasMemoryProgram = $agency->services->contains(fn ($s) => str_contains(mb_strtolower($s->name), 'memory'));

        if ($isMemoryCategory) {
            return ['score' => 100, 'applicable' => true, 'detail' => 'Specializes in Memory Care'];
        }
        if ($hasMemoryProgram) {
            return ['score' => 80, 'applicable' => true, 'detail' => 'Offers Memory Care programming'];
        }

        return ['score' => 20, 'applicable' => true, 'detail' => 'No dedicated memory care programming listed'];
    }

    private function scoreMobility(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->mobility) {
            return ['score' => null, 'applicable' => false, 'detail' => 'No mobility needs specified'];
        }

        $highNeedCategories = ['nursing_home', 'memory_care'];
        $isHighNeed = in_array($careSeeker->mobility->value, ['wheelchair', 'bedbound'], true);

        if (!$isHighNeed) {
            return ['score' => 85, 'applicable' => true, 'detail' => 'Suitable for current mobility level'];
        }

        $suited = in_array($agency->category?->code, $highNeedCategories, true) || $agency->is_wheelchair_accessible;

        return ['score' => $suited ? 90 : 35, 'applicable' => true, 'detail' => $suited ? 'Equipped for higher mobility support needs' : 'May not fully support higher mobility needs'];
    }

    private function scoreAvailability(Agency $agency): array
    {
        return [
            'score' => $agency->has_availability ? 100 : 20,
            'applicable' => true,
            'detail' => $agency->has_availability ? 'Currently accepting new residents' : 'Limited availability — waitlist likely',
        ];
    }

    private function scoreGenderPreference(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->gender || $agency->gender_served?->value === 'any') {
            return ['score' => null, 'applicable' => false, 'detail' => 'Not applicable'];
        }

        $matches = mb_strtolower($careSeeker->gender) === $agency->gender_served->value;

        return ['score' => $matches ? 100 : 0, 'applicable' => true, 'detail' => $matches ? 'Matches gender-specific care setting' : 'This agency serves ' . $agency->gender_served->label()];
    }

    private function scoreVeteranBenefits(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->is_veteran) {
            return ['score' => null, 'applicable' => false, 'detail' => 'Not applicable'];
        }

        return [
            'score' => $agency->is_veteran_friendly ? 100 : 30,
            'applicable' => true,
            'detail' => $agency->is_veteran_friendly ? 'Experienced with veteran benefits' : 'No specific veteran program listed',
        ];
    }

    private function scoreReligiousPreference(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->religious_preference) {
            return ['score' => null, 'applicable' => false, 'detail' => 'No religious preference specified'];
        }
        if (!$agency->religious_affiliation) {
            return ['score' => 50, 'applicable' => true, 'detail' => 'No religious affiliation listed'];
        }

        $matches = mb_strtolower($agency->religious_affiliation) === mb_strtolower($careSeeker->religious_preference);

        return ['score' => $matches ? 100 : 30, 'applicable' => true, 'detail' => $matches ? $agency->religious_affiliation . ' affiliated' : $agency->religious_affiliation . ' affiliated (different from preference)'];
    }

    private function scorePetFriendly(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->wants_pet_friendly) {
            return ['score' => null, 'applicable' => false, 'detail' => 'Not applicable'];
        }

        return ['score' => $agency->is_pet_friendly ? 100 : 20, 'applicable' => true, 'detail' => $agency->is_pet_friendly ? 'Pet friendly' : 'Not listed as pet friendly'];
    }

    private function scoreAccessibility(CareSeeker $careSeeker, Agency $agency): array
    {
        if (!$careSeeker->mobility || !in_array($careSeeker->mobility->value, ['wheelchair', 'bedbound'], true)) {
            return ['score' => null, 'applicable' => false, 'detail' => 'Not applicable'];
        }

        return ['score' => $agency->is_wheelchair_accessible ? 100 : 25, 'applicable' => true, 'detail' => $agency->is_wheelchair_accessible ? 'Wheelchair accessible facility' : 'Accessibility features not confirmed'];
    }

    private function scoreReviewRating(Agency $agency): array
    {
        if ($agency->review_score === null) {
            return ['score' => null, 'applicable' => false, 'detail' => 'No reviews yet'];
        }

        $score = min(100, (float) $agency->review_score * 20);

        return ['score' => round($score, 1), 'applicable' => true, 'detail' => number_format((float) $agency->review_score, 1) . ' star rating'];
    }

    private function scoreAgencyQuality(Agency $agency): array
    {
        $points = 0;
        $points += $agency->certifications->isNotEmpty() ? 25 : 0;
        $points += $agency->services->isNotEmpty() ? 25 : 0;
        $points += $agency->pricing->isNotEmpty() ? 25 : 0;
        $points += $agency->isOnboardingComplete() ? 25 : 0;

        return [
            'score' => $points,
            'applicable' => true,
            'detail' => $points >= 75 ? 'Complete, detailed agency profile' : 'Agency profile is missing some details',
        ];
    }

    private function scoreVerificationStatus(Agency $agency): array
    {
        return [
            'score' => $agency->isOnboardingComplete() ? 100 : 40,
            'applicable' => true,
            'detail' => $agency->isOnboardingComplete() ? 'Verified listing' : 'Onboarding incomplete',
        ];
    }
}
