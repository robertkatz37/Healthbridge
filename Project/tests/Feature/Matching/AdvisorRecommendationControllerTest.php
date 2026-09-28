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
    $this->family = Family::factory()->create();
    $this->careSeeker = $this->family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $this->lead = $this->advisor->leads()->create(['family_id' => $this->family->id, 'care_seeker_id' => $this->careSeeker->id, 'status' => 'assigned', 'source' => 'manual']);
});

test('advisor sees a prompt when no needs assessment exists', function () {
    $response = $this->actingAs($this->advisorUser)->get(route('advisor.leads.recommendations.index', $this->lead));

    $response->assertOk();
    $response->assertViewIs('advisor.recommendations.needs-assessment-required');
});

test('advisor can generate recommendations for their assigned lead', function () {
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->create(['status' => 'published']);

    $response = $this->actingAs($this->advisorUser)->post(route('advisor.leads.recommendations.generate', $this->lead));

    $response->assertRedirect();
    expect(MatchResult::count())->toBeGreaterThan(0);
});

test('advisor cannot generate recommendations for a lead assigned to another advisor', function () {
    $otherAdvisor = Advisor::factory()->create();
    $otherLead = $otherAdvisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->advisorUser)->post(route('advisor.leads.recommendations.generate', $otherLead));

    $response->assertStatus(403);
});

test('advisor can approve a match result', function () {
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    $matchResult = MatchResult::first();

    $response = $this->actingAs($this->advisorUser)->post(route('advisor.leads.recommendations.approve', [$this->lead, $matchResult]));

    $response->assertRedirect();
    expect($matchResult->fresh()->is_advisor_approved)->toBeTrue();
});

test('advisor can hide a match result from the family with a note, without altering the score', function () {
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    $matchResult = MatchResult::first();
    $originalScore = $matchResult->compatibility_score;

    $response = $this->actingAs($this->advisorUser)->post(route('advisor.leads.recommendations.hide', [$this->lead, $matchResult]), [
        'advisor_override_note' => 'Not a good cultural fit based on past experience.',
    ]);

    $response->assertRedirect();
    $matchResult->refresh();
    expect($matchResult->is_hidden_by_advisor)->toBeTrue();
    expect($matchResult->advisor_override_note)->toBe('Not a good cultural fit based on past experience.');
    expect((string) $matchResult->compatibility_score)->toBe((string) $originalScore);
});

test('regression: hiding a match result is immediately reflected in a cached family view, not stale for the cache TTL', function () {
    // Reproduces a real bug found via end-to-end smoke testing: toggling
    // a curation flag (approve/hide/shortlist/reorder) mutates the
    // MatchResult row directly, bypassing AgencyRecommendationService —
    // without an explicit cache invalidation call, a family reading
    // through getRecommendations()'s Cache::remember() would keep
    // seeing the pre-toggle state for up to CACHE_TTL_SECONDS.
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $agency = Agency::factory()->create(['status' => 'published']);
    $recommendations = app(\App\Services\Matching\AgencyRecommendationService::class);

    // Populate the cache first, exactly as the family's own page view would.
    $recommendations->getRecommendations($this->careSeeker);
    $matchResult = MatchResult::first();

    $this->actingAs($this->advisorUser)->post(route('advisor.leads.recommendations.hide', [$this->lead, $matchResult]), [
        'advisor_override_note' => 'Testing cache invalidation',
    ]);

    $familyView = $recommendations->forFamily($this->careSeeker, includeBelowThreshold: true);

    expect($familyView->pluck('agency_id'))->not->toContain($agency->id);
});

test('advisor can reorder match results for the family shortlist', function () {
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->count(3)->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    $ids = MatchResult::pluck('id')->shuffle()->values();

    $response = $this->actingAs($this->advisorUser)->postJson(route('advisor.leads.recommendations.reorder', $this->lead), [
        'order' => $ids->all(),
    ]);

    $response->assertOk();
    foreach ($ids as $index => $id) {
        expect(MatchResult::find($id)->sort_order)->toBe($index);
    }
});

test('advisor manager can view and manage recommendations for a team members lead', function () {
    $managerUser = User::factory()->create()->assignRole('advisor_manager');
    $manager = Advisor::create(['user_id' => $managerUser->id, 'is_active' => true]);
    $this->advisor->update(['advisor_manager_id' => $manager->id]);
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->create(['status' => 'published']);

    $response = $this->actingAs($managerUser)->get(route('advisor.leads.recommendations.index', $this->lead));

    $response->assertOk();
});

test('the family shortlist panel reflects agencies the advisor has approved, in sort_order', function () {
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->count(3)->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    $results = MatchResult::all();

    $results[0]->update(['is_advisor_approved' => true, 'sort_order' => 1]);
    $results[1]->update(['is_advisor_approved' => true, 'sort_order' => 0]);
    // results[2] left un-approved

    $response = $this->actingAs($this->advisorUser)->get(route('advisor.leads.recommendations.index', $this->lead));

    $shortlist = $response->viewData('shortlist');
    expect($shortlist->count())->toBe(2);
    expect($shortlist->pluck('id')->all())->toBe([$results[1]->id, $results[0]->id]); // sort_order 0 before 1
});

test('generate recommendations button label reflects whether results already exist', function () {
    $response = $this->actingAs($this->advisorUser)->get(route('advisor.leads.recommendations.index', $this->lead));
    // No assessment yet — this hits the prompt view, so set one up first.
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);

    $firstView = $this->actingAs($this->advisorUser)->get(route('advisor.leads.recommendations.index', $this->lead));
    $firstView->assertSee('Generate Recommendations');
    $firstView->assertDontSee('Regenerate Recommendations');

    Agency::factory()->create(['status' => 'published']);
    app(\App\Services\Matching\AgencyRecommendationService::class)->getRecommendations($this->careSeeker, forceRegenerate: true);

    $secondView = $this->actingAs($this->advisorUser)->get(route('advisor.leads.recommendations.index', $this->lead));
    $secondView->assertSee('Regenerate Recommendations');
});

test('unassigned advisor cannot access recommendations for a lead they do not own', function () {
    $otherAdvisorUser = User::factory()->create()->assignRole('advisor');
    Advisor::create(['user_id' => $otherAdvisorUser->id, 'is_active' => true]);

    $response = $this->actingAs($otherAdvisorUser)->get(route('advisor.leads.recommendations.index', $this->lead));

    $response->assertStatus(403);
});
