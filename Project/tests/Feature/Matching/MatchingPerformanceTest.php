<?php

use App\Models\Agency;
use App\Models\Family;
use App\Services\Matching\MatchingEngineService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->withoutVite();
});

test('generating recommendations does not exhibit N+1 query growth as agency count increases', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create([
        'first_name' => 'Perf', 'last_name' => 'Test', 'care_type_needed' => 'assisted_living',
        'budget_min' => 2000, 'budget_max' => 4000, 'preferred_city' => 'Austin', 'preferred_state' => 'TX',
    ]);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);

    // Baseline with 5 agencies
    Agency::factory()->count(5)->create(['status' => 'published']);
    DB::enableQueryLog();
    app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    $queryCountWith5 = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();

    // 20 more agencies (25 total) — if scoring is properly eager-loaded,
    // the query count should NOT scale linearly with agency count; it
    // should stay roughly flat (one query per relation type, not one
    // per agency per relation).
    Agency::factory()->count(20)->create(['status' => 'published']);
    DB::enableQueryLog();
    app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    $queryCountWith25 = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Allow some growth (one query per persisted match_result row via
    // updateOrCreate is expected and acceptable — that's not N+1 on
    // *relations*, it's the actual write workload) but assert it isn't
    // multiplying by the full relation count (services, coverage,
    // certifications, pricing) per agency, which would indicate the
    // eager loading in MatchingRuleEngine::candidateQuery() regressed.
    $agencyCountIncrease = 20;
    $maxAcceptableGrowth = $agencyCountIncrease * 2; // generous ceiling, not per-relation multiplication

    expect($queryCountWith25 - $queryCountWith5)->toBeLessThan($maxAcceptableGrowth);
});

test('candidate query eager loads all relations MatchingScoreService touches', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Eager', 'last_name' => 'Load']);

    $agency = Agency::factory()->create(['status' => 'published']);
    $agency->services()->create(['name' => 'Personal Care']);
    $agency->certifications()->create(['name' => 'Test Cert']);
    $agency->coverage()->create(['city' => 'Austin', 'state' => 'TX']);

    $candidates = app(\App\Services\Matching\MatchingRuleEngine::class)->candidateQuery($careSeeker)->get();

    foreach (['category', 'services', 'coverage', 'certifications', 'pricing'] as $relation) {
        expect($candidates->first()->relationLoaded($relation))->toBeTrue();
    }
});

test('scoring 50 agencies completes in a reasonable time', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create([
        'first_name' => 'Speed', 'last_name' => 'Test', 'care_type_needed' => 'assisted_living', 'budget_min' => 2000, 'budget_max' => 4000,
    ]);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->count(50)->create(['status' => 'published']);

    $start = microtime(true);
    app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    $elapsed = microtime(true) - $start;

    expect($elapsed)->toBeLessThan(10.0);
});

test('cached recommendation reads do not re-hit the database on repeated calls within the cache window', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Cache', 'last_name' => 'Test']);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->count(3)->create(['status' => 'published']);

    $service = app(\App\Services\Matching\AgencyRecommendationService::class);
    $service->getRecommendations($careSeeker);

    DB::enableQueryLog();
    $service->getRecommendations($careSeeker);
    $service->getRecommendations($careSeeker);
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Two calls to an already-cached, non-stale read should not each
    // issue a fresh match_results query.
    expect($queryCount)->toBeLessThan(6);
});
