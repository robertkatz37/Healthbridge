<?php

use App\Models\Agency;
use App\Models\Family;
use App\Services\Matching\MatchingEngineService;
use Database\Seeders\DemoDataSeeder;

/**
 * Regression test for a real reported bug: a family in a real US city saw
 * only ONE recommended agency despite 25 published agencies existing.
 * Root cause: DemoDataSeeder never overrode Agency::factory()'s default
 * city/state, which uses Faker's fake()->city() — fictional names
 * scattered uniformly across all 50 states. Once the Distance factor
 * became a genuine hard filter (a prior fix), this correctly excluded
 * almost every seeded agency for any single test city, since most of
 * them randomly landed 1000+ miles away. Fixed by having the demo seeder
 * place agencies in a curated list of real cities, evenly distributed
 * (not randomly, which could still leave a given city under-represented
 * by chance) so every city in the list has multiple genuinely nearby
 * options.
 */
test('REGRESSION: every demo city has multiple published agencies within default matching distance', function () {
    $this->seed(DemoDataSeeder::class);

    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create([
        'first_name' => 'Regression', 'last_name' => 'Test',
        'preferred_city' => 'Austin', 'preferred_state' => 'TX',
    ]);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);

    $results = app(MatchingEngineService::class)->generateRecommendations($careSeeker);

    expect($results->count())->toBeGreaterThan(1);
});

test('demo agencies are placed in real, resolvable cities, not scattered fictional ones', function () {
    $this->seed(DemoDataSeeder::class);

    $cityDistribution = Agency::where('status', 'published')
        ->selectRaw('city, count(*) as count')
        ->groupBy('city')
        ->pluck('count', 'city');

    // Every city should have at least 2 agencies — guards against the
    // pure-random-selection version of this fix, which could still
    // leave some cities under-represented by chance.
    foreach ($cityDistribution as $city => $count) {
        expect($count)->toBeGreaterThanOrEqual(2);
    }

    // A reasonable number of distinct cities, not everything piled into one.
    expect($cityDistribution->count())->toBeGreaterThanOrEqual(10);
});
