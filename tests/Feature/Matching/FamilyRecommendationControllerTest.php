<?php

use App\Models\Agency;
use App\Models\Family;
use App\Models\MatchResult;
use App\Models\User;
use App\Services\Matching\MatchingEngineService;

beforeEach(function () {
    $this->withoutVite();
    $this->familyUser = User::factory()->create()->assignRole('family');
    $this->family = Family::create(['user_id' => $this->familyUser->id]);
    $this->careSeeker = $this->family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
});

test('family sees a prompt to complete the needs assessment when none exists', function () {
    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.recommendations.index', $this->careSeeker));

    $response->assertOk();
    $response->assertViewIs('family.recommendations.needs-assessment-required');
});

test('family can view recommendations after completing the needs assessment', function () {
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->create(['status' => 'published']);

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.recommendations.index', $this->careSeeker));

    $response->assertOk();
    $response->assertViewIs('family.recommendations.index');
});

test('family cannot view another familys care seeker recommendations', function () {
    $otherFamily = Family::factory()->create();
    $otherCareSeeker = $otherFamily->careSeekers()->create(['first_name' => 'Other', 'last_name' => 'Seeker']);

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.recommendations.index', $otherCareSeeker));

    $response->assertStatus(403);
});

test('family can regenerate their own recommendations', function () {
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->create(['status' => 'published']);

    $response = $this->actingAs($this->familyUser)->post(route('family.care-seekers.recommendations.regenerate', $this->careSeeker));

    $response->assertRedirect();
    expect(MatchResult::count())->toBeGreaterThan(0);
});

test('family can toggle shortlist on their own match result', function () {
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    $matchResult = MatchResult::first();

    $response = $this->actingAs($this->familyUser)->post(route('family.care-seekers.recommendations.shortlist', [$this->careSeeker, $matchResult]));

    $response->assertRedirect();
    expect($matchResult->fresh()->is_family_shortlisted)->toBeTrue();

    // Toggle back off
    $this->actingAs($this->familyUser)->post(route('family.care-seekers.recommendations.shortlist', [$this->careSeeker, $matchResult]));
    expect($matchResult->fresh()->is_family_shortlisted)->toBeFalse();
});

test('regression: shortlisting is immediately reflected in a subsequent cached read, not stale for the cache TTL', function () {
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->create(['status' => 'published']);
    $recommendations = app(\App\Services\Matching\AgencyRecommendationService::class);

    // Populate the cache first, exactly as loading the page once would.
    $recommendations->getRecommendations($this->careSeeker);
    $matchResult = \App\Models\MatchResult::first();

    $this->actingAs($this->familyUser)->post(route('family.care-seekers.recommendations.shortlist', [$this->careSeeker, $matchResult]));

    $freshRead = $recommendations->getRecommendations($this->careSeeker);

    expect($freshRead->firstWhere('id', $matchResult->id)->is_family_shortlisted)->toBeTrue();
});

test('family cannot shortlist a match result belonging to another familys care seeker', function () {
    $otherFamily = Family::factory()->create();
    $otherCareSeeker = $otherFamily->careSeekers()->create(['first_name' => 'Other', 'last_name' => 'Seeker']);
    $otherCareSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($otherCareSeeker);
    $matchResult = MatchResult::first();

    $response = $this->actingAs($this->familyUser)->post(route('family.care-seekers.recommendations.shortlist', [$this->careSeeker, $matchResult]));

    $response->assertStatus(403);
});

test('sorting by distance orders results correctly', function () {
    $this->careSeeker->update(['lat' => 30.2672, 'lng' => -97.7431]);
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $near = Agency::factory()->create(['status' => 'published', 'lat' => 30.28, 'lng' => -97.75]);
    $far = Agency::factory()->create(['status' => 'published', 'lat' => 32.7767, 'lng' => -96.7970]);

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.recommendations.index', ['care_seeker' => $this->careSeeker, 'sort' => 'distance']));

    $results = $response->viewData('results');
    expect($results->first()->agency_id)->toBe($near->id);
});

test('family can filter recommendations by minimum match score', function () {
    $this->careSeeker->update(['care_type_needed' => 'memory_care', 'budget_min' => 3000, 'budget_max' => 5000]);
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $memoryCareCategory = \App\Models\AgencyCategory::where('code', 'memory_care')->first();
    $strongFit = Agency::factory()->create(['status' => 'published', 'agency_category_id' => $memoryCareCategory->id, 'min_monthly_cost' => 3500, 'max_monthly_cost' => 4500]);
    $weakFit = Agency::factory()->create(['status' => 'published', 'min_monthly_cost' => 9000, 'max_monthly_cost' => 9500]);

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.recommendations.index', [
        'care_seeker' => $this->careSeeker, 'min_score' => 60, 'show_all' => 1,
    ]));

    $results = $response->viewData('results');
    foreach ($results as $result) {
        expect((float) $result->compatibility_score)->toBeGreaterThanOrEqual(60);
    }
});

test('family can filter recommendations by minimum rating', function () {
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $highRated = Agency::factory()->create(['status' => 'published', 'review_score' => 4.8]);
    $lowRated = Agency::factory()->create(['status' => 'published', 'review_score' => 2.0]);

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.recommendations.index', [
        'care_seeker' => $this->careSeeker, 'min_rating' => 4, 'show_all' => 1,
    ]));

    $results = $response->viewData('results');
    expect($results->pluck('agency_id'))->toContain($highRated->id);
    expect($results->pluck('agency_id'))->not->toContain($lowRated->id);
});

test('family can filter recommendations by maximum distance', function () {
    $this->careSeeker->update(['lat' => 30.2672, 'lng' => -97.7431]);
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $near = Agency::factory()->create(['status' => 'published', 'lat' => 30.28, 'lng' => -97.75]);
    $far = Agency::factory()->create(['status' => 'published', 'lat' => 32.7767, 'lng' => -96.7970]);

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.recommendations.index', [
        'care_seeker' => $this->careSeeker, 'max_distance' => 20, 'show_all' => 1,
    ]));

    $results = $response->viewData('results');
    expect($results->pluck('agency_id'))->toContain($near->id);
    expect($results->pluck('agency_id'))->not->toContain($far->id);
});

test('agency users cannot access any family recommendation route', function () {
    $agencyUser = User::factory()->create()->assignRole('agency_owner');
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);

    $response = $this->actingAs($agencyUser)->get(route('family.care-seekers.recommendations.index', $this->careSeeker));

    $response->assertStatus(403);
});
