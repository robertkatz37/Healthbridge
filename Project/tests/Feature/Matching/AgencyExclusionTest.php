<?php

use App\Models\Agency;
use App\Models\Family;
use App\Models\MatchResult;
use App\Models\User;
use App\Services\Matching\MatchingEngineService;

beforeEach(function () {
    $this->withoutVite();
});

test('agencies must not see their internal matching score - no agency route exposes match_results', function () {
    // Enumerate every registered route and assert none of them, when
    // scoped under the agency.* name prefix, touch MatchResult at all -
    // the requirement is that this data surface does not exist for
    // agencies, not merely that it's access-denied.
    $agencyRoutes = collect(\Illuminate\Support\Facades\Route::getRoutes())
        ->filter(fn ($route) => str_starts_with($route->getName() ?? '', 'agency.'));

    foreach ($agencyRoutes as $route) {
        $action = $route->getActionName();
        expect($action)->not->toContain('MatchResult');
        expect($action)->not->toContain('Recommendation');
    }
});

test('an agency owner cannot view their own compatibility score via the public agency page', function () {
    $agencyUser = User::factory()->create()->assignRole('agency_owner');
    $agency = Agency::factory()->create(['user_id' => $agencyUser->id, 'status' => 'published']);

    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    app(MatchingEngineService::class)->generateRecommendations($careSeeker);

    $response = $this->actingAs($agencyUser)->get(route('agencies.show', $agency));

    $response->assertOk();
    $response->assertDontSee('compatibility_score');
    $response->assertDontSee('match_results');
});

test('agency owner has no policy ability to view a MatchResult', function () {
    $agencyUser = User::factory()->create()->assignRole('agency_owner');
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    $matchResult = MatchResult::first();

    // No MatchResultPolicy is registered at all — Laravel's default
    // Gate response for an unregistered policy/ability is to deny.
    expect($agencyUser->can('view', $matchResult))->toBeFalse();
});
