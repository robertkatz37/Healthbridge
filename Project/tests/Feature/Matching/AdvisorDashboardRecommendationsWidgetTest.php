<?php

use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\MatchResult;
use App\Models\User;
use App\Services\Matching\MatchingEngineService;

beforeEach(function () {
    $this->withoutVite();
    $this->advisorUser = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->advisorUser->id, 'is_active' => true]);
});

test('a lead whose care seeker completed the assessment but has no recommendations yet appears on the dashboard widget', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Needs', 'last_name' => 'Recs']);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'care_seeker_id' => $careSeeker->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->advisorUser)->get(route('advisor.dashboard'));

    $response->assertOk();
    $response->assertSee('Recommendations Ready to Generate');
    $response->assertSee('Generate Recommendations');
    $needing = $response->viewData('leadsNeedingRecommendations');
    expect($needing->pluck('id'))->toContain($lead->id);
});

test('a lead that already has recommendations does not appear on the widget', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Already', 'last_name' => 'Done']);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'care_seeker_id' => $careSeeker->id, 'status' => 'assigned', 'source' => 'manual']);
    Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($careSeeker);

    $response = $this->actingAs($this->advisorUser)->get(route('advisor.dashboard'));

    $needing = $response->viewData('leadsNeedingRecommendations');
    expect($needing->pluck('id'))->not->toContain($lead->id);
});

test('a lead whose care seeker has not completed the assessment does not appear on the widget', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'No', 'last_name' => 'Assessment']);
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'care_seeker_id' => $careSeeker->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->advisorUser)->get(route('advisor.dashboard'));

    $needing = $response->viewData('leadsNeedingRecommendations');
    expect($needing->pluck('id'))->not->toContain($lead->id);
});

test('clicking Generate Recommendations directly from the dashboard widget works', function () {
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Click', 'last_name' => 'Generate']);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'care_seeker_id' => $careSeeker->id, 'status' => 'assigned', 'source' => 'manual']);
    Agency::factory()->create(['status' => 'published']);

    $response = $this->actingAs($this->advisorUser)->post(route('advisor.leads.recommendations.generate', $lead));

    $response->assertRedirect();
    expect(MatchResult::count())->toBeGreaterThan(0);
});
