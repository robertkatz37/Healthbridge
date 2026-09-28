<?php

namespace App\Services\Matching;

/**
 * Computes distance in miles between two points via the Haversine
 * formula when both have coordinates. No geocoding provider is
 * integrated in this phase (Google Places is Phase 20/Search's scope,
 * not Matching), so `care_seekers.lat/lng` are populated only where an
 * advisor or family has entered them manually (or a future phase wires
 * up geocoding) — when either point lacks coordinates, this falls back
 * to a coarse, clearly-labeled city/state tier rather than presenting a
 * fabricated mile figure with false precision.
 */
class DistanceCalculatorService
{
    private const EARTH_RADIUS_MILES = 3958.8;

    public function milesBetween(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(self::EARTH_RADIUS_MILES * $c, 1);
    }

    /**
     * Coarse fallback used whenever coordinates aren't available for
     * one or both points — returns a tier label and an associated
     * "distance-like" score (0-100, not miles) for use by
     * MatchingScoreService when a real mile figure can't be shown.
     */
    public function locationTier(?string $city1, ?string $state1, ?string $city2, ?string $state2): array
    {
        if (!$state1 || !$state2) {
            return ['tier' => 'unknown', 'score' => 40];
        }

        if ($city1 && $city2 && mb_strtolower(trim($city1)) === mb_strtolower(trim($city2)) && mb_strtolower($state1) === mb_strtolower($state2)) {
            return ['tier' => 'same_city', 'score' => 100];
        }

        if (mb_strtolower($state1) === mb_strtolower($state2)) {
            return ['tier' => 'same_state', 'score' => 55];
        }

        return ['tier' => 'different_state', 'score' => 15];
    }
}
