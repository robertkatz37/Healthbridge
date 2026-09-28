<?php

use App\Enums\LeadStatus;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\Family;
use App\Services\Matching\AgencyRecommendationService;
use App\Services\Matching\MatchExplanationService;
use App\Services\Matching\MatchingEngineService;

beforeEach(function () {
    $this->withoutVite();
    $this->memoryCareCategory = AgencyCategory::where('code', 'memory_care')->first();
});

test('a near-perfect match produces an excellent-match tier and mostly checkmark reasons', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create([
        'first_name' => 'Grace', 'last_name' => 'Lee', 'care_type_needed' => 'memory_care',
        'memory_status' => 'moderate', 'budget_min' => 3000, 'budget_max' => 5000,
        'preferred_city' => 'Austin', 'preferred_state' => 'TX',
    ]);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);

    $agency = Agency::factory()->create([
        'status' => 'published', 'agency_category_id' => $this->memoryCareCategory->id,
        'city' => 'Austin', 'state' => 'TX', 'min_monthly_cost' => 3500, 'max_monthly_cost' => 4500,
        'review_score' => 4.9, 'has_availability' => true,
    ]);

    $results = app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    $result = $results->firstWhere('agency_id', $agency->id);
    $explanation = app(MatchExplanationService::class)->explain($result->score_breakdown);

    expect($result->compatibility_score)->toBeGreaterThan(75);
    expect($explanation['tier'])->toBeIn(['Excellent Match', 'Strong Match']);
    expect(count($explanation['reasons']))->toBeGreaterThan(count($explanation['missing']));
});

test('a poor match produces a low tier and more missing requirements than reasons', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create([
        'first_name' => 'Tom', 'last_name' => 'Reed', 'care_type_needed' => 'home_care',
        'budget_min' => 1500, 'budget_max' => 2000, 'preferred_city' => 'Miami', 'preferred_state' => 'FL',
        'is_veteran' => true, 'wants_pet_friendly' => true,
    ]);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);

    // Genuinely close to the family (Fort Lauderdale is ~25 miles from
    // Miami) so it isn't hard-excluded by the distance filter — distance
    // is now a real, functional exclusion (see CityCoordinateResolver /
    // DATABASE_DECISIONS.md), so a cross-country agency would simply
    // never appear in results rather than scoring low, and "same state"
    // alone isn't close enough in a long state like Florida (Miami to
    // Tallahassee is ~480 miles). This test is specifically about a
    // poor fit *score*, not distance exclusion, which has its own test
    // below.
    $agency = Agency::factory()->create([
        'status' => 'published', 'agency_category_id' => $this->memoryCareCategory->id,
        'city' => 'Fort Lauderdale', 'state' => 'FL', 'min_monthly_cost' => 8000, 'max_monthly_cost' => 9000,
        'is_veteran_friendly' => false, 'is_pet_friendly' => false, 'has_availability' => false,
    ]);

    $results = app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    $result = $results->firstWhere('agency_id', $agency->id);
    expect($result)->not->toBeNull();
    $explanation = app(MatchExplanationService::class)->explain($result->score_breakdown);

    expect($result->compatibility_score)->toBeLessThan(50);
    expect($explanation['tier'])->toBeIn(['Fair Match', 'Limited Match']);
});

test('an agency far outside the configured max distance is excluded from results entirely, not merely scored low', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create([
        'first_name' => 'Cross', 'last_name' => 'Country', 'preferred_city' => 'Miami', 'preferred_state' => 'FL',
    ]);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);

    $tooFarAgency = Agency::factory()->create(['status' => 'published', 'city' => 'Seattle', 'state' => 'WA']);

    $results = app(MatchingEngineService::class)->generateRecommendations($careSeeker);

    expect($results->pluck('agency_id'))->not->toContain($tooFarAgency->id);
});

test('every score_breakdown factor referenced in an explanation reason is internally consistent', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create([
        'first_name' => 'Ana', 'last_name' => 'Cruz', 'care_type_needed' => 'assisted_living', 'budget_min' => 2500, 'budget_max' => 4000,
    ]);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $agency = Agency::factory()->create(['status' => 'published']);

    $results = app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    $result = $results->firstWhere('agency_id', $agency->id);
    $explanation = app(MatchExplanationService::class)->explain($result->score_breakdown);

    // Every reason/missing detail string must trace back to an actual
    // applicable factor's own detail text - the explanation service must
    // not invent text unrelated to the computed breakdown.
    $allDetails = collect($result->score_breakdown['factors'])
        ->filter(fn ($f) => $f['applicable'])
        ->pluck('detail')
        ->all();

    foreach (array_merge($explanation['reasons'], $explanation['missing']) as $text) {
        expect($allDetails)->toContain($text);
    }
});

test('draft and suspended agencies never appear in recommendations', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Amir', 'last_name' => 'Khan']);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);

    $draftAgency = Agency::factory()->create(['status' => 'draft']);
    $suspendedAgency = Agency::factory()->create(['status' => 'suspended']);
    $publishedAgency = Agency::factory()->create(['status' => 'published']);

    $results = app(MatchingEngineService::class)->generateRecommendations($careSeeker);

    expect($results->pluck('agency_id'))->not->toContain($draftAgency->id);
    expect($results->pluck('agency_id'))->not->toContain($suspendedAgency->id);
    expect($results->pluck('agency_id'))->toContain($publishedAgency->id);
});

test('regenerating recommendations preserves advisor approval and family shortlist flags', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Priya', 'last_name' => 'Nair']);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $agency = Agency::factory()->create(['status' => 'published']);

    $results = app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    $result = $results->firstWhere('agency_id', $agency->id);
    $result->update(['is_advisor_approved' => true, 'is_family_shortlisted' => true, 'sort_order' => 3]);

    app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    $result->refresh();

    expect($result->is_advisor_approved)->toBeTrue();
    expect($result->is_family_shortlisted)->toBeTrue();
    expect($result->sort_order)->toBe(3);
});

test('recommendations respect the configured max distance as a hard exclusion', function () {
    app(\App\Services\Settings\SettingsService::class)->set('matching_max_distance_miles', '50', 'matching', 'int');

    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Lou', 'last_name' => 'Park', 'lat' => 30.2672, 'lng' => -97.7431]);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);

    $nearAgency = Agency::factory()->create(['status' => 'published', 'lat' => 30.3, 'lng' => -97.75]); // ~2 miles
    $farAgency = Agency::factory()->create(['status' => 'published', 'lat' => 39.7392, 'lng' => -104.9903]); // Denver, ~900+ miles

    $results = app(MatchingEngineService::class)->generateRecommendations($careSeeker);

    expect($results->pluck('agency_id'))->toContain($nearAgency->id);
    expect($results->pluck('agency_id'))->not->toContain($farAgency->id);
});

test('AgencyRecommendationService forFamily hides results below the configured threshold by default', function () {
    app(\App\Services\Settings\SettingsService::class)->set('matching_min_score_threshold', '60', 'matching', 'int');

    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Deb', 'last_name' => 'Wu', 'care_type_needed' => 'home_care', 'budget_min' => 1000, 'budget_max' => 1200]);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $poorFitAgency = Agency::factory()->create(['status' => 'published', 'min_monthly_cost' => 9000, 'max_monthly_cost' => 9500]);

    app(MatchingEngineService::class)->generateRecommendations($careSeeker);

    $familyView = app(AgencyRecommendationService::class)->forFamily($careSeeker);
    $advisorView = app(AgencyRecommendationService::class)->forAdvisor($careSeeker);

    $poorResult = $advisorView->firstWhere('agency_id', $poorFitAgency->id);
    if ($poorResult->compatibility_score < 60) {
        expect($familyView->pluck('agency_id'))->not->toContain($poorFitAgency->id);
    }
    expect($advisorView->pluck('agency_id'))->toContain($poorFitAgency->id);
});
