<?php

use App\Enums\ReferralStatus;
use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\MatchResult;
use App\Models\Referral;
use App\Models\User;
use App\Services\Matching\MatchingEngineService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    $this->advisorUser = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->advisorUser->id, 'is_active' => true]);
    $this->family = Family::factory()->create();
    $this->careSeeker = $this->family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $this->lead = $this->family->leads()->create(['care_seeker_id' => $this->careSeeker->id, 'advisor_id' => $this->advisor->id, 'status' => 'assigned', 'source' => 'manual']);
});

test('the recommendations page renders the Send Referral panel without error', function () {
    Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    MatchResult::first()->update(['is_advisor_approved' => true]);

    $response = $this->actingAs($this->advisorUser)->get(route('advisor.leads.recommendations.index', $this->lead));

    $response->assertOk();
    $response->assertSee('Family Shortlist');
    $response->assertSee('Send Referral');
});

test('advisor can send a referral to a shortlisted agency directly from the recommendations page', function () {
    $agency = Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    MatchResult::first()->update(['is_advisor_approved' => true]);

    $response = $this->actingAs($this->advisorUser)->post(route('advisor.leads.referrals.store', $this->lead), [
        'agency_ids' => [$agency->id],
        'priority' => 'high',
        'notes' => 'Please prioritize this family.',
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();

    $referral = Referral::where('lead_id', $this->lead->id)->where('agency_id', $agency->id)->first();
    expect($referral)->not->toBeNull();
    expect($referral->status)->toBe(ReferralStatus::SentToAgency);
    expect($referral->priority->value)->toBe('high');
    expect($referral->notes()->count())->toBe(1);

    Notification::assertSentTo($agency->user, \App\Notifications\Referral\ReferralSent::class);
});

test('advisor can send referrals to multiple agencies at once', function () {
    $agency1 = Agency::factory()->create(['status' => 'published']);
    $agency2 = Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    MatchResult::whereIn('agency_id', [$agency1->id, $agency2->id])->update(['is_advisor_approved' => true]);

    $response = $this->actingAs($this->advisorUser)->post(route('advisor.leads.referrals.store', $this->lead), [
        'agency_ids' => [$agency1->id, $agency2->id],
        'priority' => 'medium',
    ]);

    $response->assertRedirect();
    expect(Referral::where('lead_id', $this->lead->id)->count())->toBe(2);
});

test('an advisor cannot send a referral for a lead they are not assigned to', function () {
    $otherAdvisorUser = User::factory()->create()->assignRole('advisor');
    Advisor::create(['user_id' => $otherAdvisorUser->id, 'is_active' => true]);
    $agency = Agency::factory()->create(['status' => 'published']);

    $response = $this->actingAs($otherAdvisorUser)->post(route('advisor.leads.referrals.store', $this->lead), [
        'agency_ids' => [$agency->id],
        'priority' => 'medium',
    ]);

    $response->assertStatus(403);
});

test('sending a referral to an agency that already has an open referral for this lead fails gracefully', function () {
    $agency = Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    MatchResult::first()->update(['is_advisor_approved' => true]);

    $this->actingAs($this->advisorUser)->post(route('advisor.leads.referrals.store', $this->lead), [
        'agency_ids' => [$agency->id], 'priority' => 'medium',
    ]);

    $response = $this->actingAs($this->advisorUser)->post(route('advisor.leads.referrals.store', $this->lead), [
        'agency_ids' => [$agency->id], 'priority' => 'medium',
    ]);

    $response->assertSessionHasErrors();
    expect(Referral::where('lead_id', $this->lead->id)->count())->toBe(1);
});

test('advisor can view the referrals index and a single referral detail page', function () {
    $agency = Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    MatchResult::first()->update(['is_advisor_approved' => true]);
    $this->actingAs($this->advisorUser)->post(route('advisor.leads.referrals.store', $this->lead), [
        'agency_ids' => [$agency->id], 'priority' => 'medium',
    ]);
    $referral = Referral::first();

    $indexResponse = $this->actingAs($this->advisorUser)->get(route('advisor.referrals.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee($agency->name);

    $showResponse = $this->actingAs($this->advisorUser)->get(route('advisor.referrals.show', $referral));
    $showResponse->assertOk();
    $showResponse->assertSee('Sent to Agency');
    $showResponse->assertSee('Move to Agency Accepted');
    $showResponse->assertDontSee('Move to Tour Scheduled'); // handled by dedicated Schedule Tour form once accepted, not shown pre-acceptance
});

test('advisor can update referral priority', function () {
    $agency = Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    MatchResult::first()->update(['is_advisor_approved' => true]);
    $this->actingAs($this->advisorUser)->post(route('advisor.leads.referrals.store', $this->lead), [
        'agency_ids' => [$agency->id], 'priority' => 'medium',
    ]);
    $referral = Referral::first();

    $response = $this->actingAs($this->advisorUser)->put(route('advisor.referrals.priority', $referral), ['priority' => 'high']);

    $response->assertRedirect();
    expect($referral->fresh()->priority->value)->toBe('high');
});

test('advisor can cancel a referral with a reason', function () {
    $agency = Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    MatchResult::first()->update(['is_advisor_approved' => true]);
    $this->actingAs($this->advisorUser)->post(route('advisor.leads.referrals.store', $this->lead), [
        'agency_ids' => [$agency->id], 'priority' => 'medium',
    ]);
    $referral = Referral::first();

    $response = $this->actingAs($this->advisorUser)->post(route('advisor.referrals.cancel', $referral), ['reason' => 'Family changed their mind']);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect($referral->fresh()->status)->toBe(ReferralStatus::Cancelled);
});

test('advisor can add a note to a referral with visibility flags', function () {
    $agency = Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    MatchResult::first()->update(['is_advisor_approved' => true]);
    $this->actingAs($this->advisorUser)->post(route('advisor.leads.referrals.store', $this->lead), [
        'agency_ids' => [$agency->id], 'priority' => 'medium',
    ]);
    $referral = Referral::first();

    $response = $this->actingAs($this->advisorUser)->post(route('advisor.referrals.notes.store', $referral), [
        'content' => 'Family prefers morning tours.',
        'visible_to_agency' => '1',
    ]);

    $response->assertRedirect();
    $note = $referral->notes()->latest()->first();
    expect($note->content)->toBe('Family prefers morning tours.');
    expect($note->visible_to_agency)->toBeTrue();
    expect($note->visible_to_family)->toBeFalse();
});

test('advisor can close a referral as lost with a reason, once the agency has accepted', function () {
    $agency = Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    MatchResult::first()->update(['is_advisor_approved' => true]);
    $this->actingAs($this->advisorUser)->post(route('advisor.leads.referrals.store', $this->lead), [
        'agency_ids' => [$agency->id], 'priority' => 'medium',
    ]);
    $referral = Referral::first();
    app(\App\Services\Referral\ReferralPipelineService::class)->transition($referral, ReferralStatus::AgencyAccepted, $agency->user);

    $response = $this->actingAs($this->advisorUser)->put(route('advisor.referrals.transition', $referral), [
        'status' => 'closed_lost', 'reason' => 'Family chose a different agency',
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect($referral->fresh()->status)->toBe(ReferralStatus::ClosedLost);
});

test('advisor can schedule a tour once the agency has accepted, which advances the referral status', function () {
    $agency = Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);
    MatchResult::first()->update(['is_advisor_approved' => true]);
    $this->actingAs($this->advisorUser)->post(route('advisor.leads.referrals.store', $this->lead), [
        'agency_ids' => [$agency->id], 'priority' => 'medium',
    ]);
    $referral = Referral::first();
    app(\App\Services\Referral\ReferralPipelineService::class)->transition($referral, ReferralStatus::AgencyAccepted, $agency->user);

    $response = $this->actingAs($this->advisorUser)->post(route('advisor.referrals.tours.store', $referral), [
        'requested_date' => now()->addDays(3)->toDateString(),
        'requested_time_window' => '1-3 PM',
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect($referral->fresh()->status)->toBe(ReferralStatus::TourScheduled);
    expect($referral->tourRequests()->count())->toBe(1);
});
