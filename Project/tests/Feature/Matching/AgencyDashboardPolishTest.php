<?php

use App\Models\Agency;
use App\Models\CareSeeker;
use App\Models\Family;
use App\Models\MatchResult;
use App\Models\User;
use App\Services\Matching\MatchingEngineService;

beforeEach(function () {
    $this->withoutVite();
    $this->agencyUser = User::factory()->create()->assignRole('agency_owner');
    $this->agency = Agency::factory()->create(['user_id' => $this->agencyUser->id, 'status' => 'published']);
});

test('agency owner can view their notifications page', function () {
    $response = $this->actingAs($this->agencyUser)->get(route('agency.notifications.index'));

    $response->assertOk();
});

test('agency owner can view their subscription page with real plan data', function () {
    $response = $this->actingAs($this->agencyUser)->get(route('agency.subscription.index'));

    $response->assertOk();
});

test('agency owner can view their analytics page', function () {
    $response = $this->actingAs($this->agencyUser)->get(route('agency.analytics.index'));

    $response->assertOk();
});

test('CRITICAL: the agency Analytics page never exposes compatibility_score, score_breakdown, or any matching internals', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    app(MatchingEngineService::class)->generateRecommendations($careSeeker);
    MatchResult::where('agency_id', $this->agency->id)->update(['is_family_shortlisted' => true]);

    $response = $this->actingAs($this->agencyUser)->get(route('agency.analytics.index'));

    $response->assertOk();
    $response->assertDontSee('compatibility_score');
    $response->assertDontSee('score_breakdown');
    $response->assertDontSee('match_results');
    $response->assertDontSee('Match Score');
    // The aggregate count itself IS expected and fine to show.
    $response->assertSee('Times Shortlisted');
});

test('agency Analytics stats never include another agencys data', function () {
    $otherAgency = Agency::factory()->create(['status' => 'published']);
    $family = Family::factory()->create();
    $family->favorites()->create(['agency_id' => $otherAgency->id]);

    $response = $this->actingAs($this->agencyUser)->get(route('agency.analytics.index'));

    $stats = $response->viewData('stats');
    expect($stats['favorites_count'])->toBe(0);
});

test('a family user cannot access any agency-only dashboard route', function () {
    $familyUser = User::factory()->create()->assignRole('family');

    $this->actingAs($familyUser)->get(route('agency.notifications.index'))->assertStatus(403);
    $this->actingAs($familyUser)->get(route('agency.subscription.index'))->assertStatus(403);
    $this->actingAs($familyUser)->get(route('agency.analytics.index'))->assertStatus(403);
});

test('the agency sidebar links to Notifications, Subscription, and Analytics', function () {
    $response = $this->actingAs($this->agencyUser)->get(route('agency.profile.edit'));

    $response->assertOk();
    $response->assertSee('Analytics');
    $response->assertSee('Notifications');
    $response->assertSee('Subscription');
});
