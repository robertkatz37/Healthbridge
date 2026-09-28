<?php

use App\Models\Agency;
use App\Models\Family;
use App\Models\MatchResult;
use App\Models\User;
use App\Services\Matching\MatchingEngineService;
use App\Services\Settings\SettingsService;
use Database\Seeders\AdminUserSeeder;

/**
 * Explicit, visible demonstration (not just an assertion buried in a
 * generic test) that changing a weight through the real admin HTTP
 * endpoint actually changes a subsequently-computed recommendation
 * score — per the person's explicit request to "actually test it," not
 * simply confirm it.
 */
test('DEMONSTRATION: changing veteran_benefits weight from 5 to 40 via the real admin HTTP endpoint changes the computed score', function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create([
        'first_name' => 'Weight', 'last_name' => 'Demo', 'is_veteran' => true,
        'budget_min' => 3000, 'budget_max' => 5000,
    ]);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $agency = Agency::factory()->create([
        'status' => 'published', 'is_veteran_friendly' => true,
        'min_monthly_cost' => 3500, 'max_monthly_cost' => 4500,
    ]);

    $baseWeights = app(SettingsService::class)->get('matching_weights');

    // --- Run 1: veteran_benefits weight = 5, set via the real HTTP PUT ---
    $this->actingAs($admin)->put(route('admin.settings.matching.update'), [
        'weights' => array_merge($baseWeights, ['veteran_benefits' => 5]),
        'featured_boost' => 3, 'max_distance_miles' => 100, 'min_score_threshold' => 30,
    ])->assertRedirect();

    app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    $scoreWithLowWeight = (float) MatchResult::first()->compatibility_score;

    // --- Run 2: veteran_benefits weight = 40, set via the real HTTP PUT ---
    $this->actingAs($admin)->put(route('admin.settings.matching.update'), [
        'weights' => array_merge($baseWeights, ['veteran_benefits' => 40]),
        'featured_boost' => 3, 'max_distance_miles' => 100, 'min_score_threshold' => 30,
    ])->assertRedirect();

    app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    $scoreWithHighWeight = (float) MatchResult::first()->fresh()->compatibility_score;

    dump("Score with veteran_benefits weight=5:  {$scoreWithLowWeight}%");
    dump("Score with veteran_benefits weight=40: {$scoreWithHighWeight}%");
    dump('Difference: ' . round($scoreWithHighWeight - $scoreWithLowWeight, 2) . ' points');

    expect($scoreWithHighWeight)->not->toBe($scoreWithLowWeight);
    expect($scoreWithHighWeight)->toBeGreaterThan($scoreWithLowWeight);
});
