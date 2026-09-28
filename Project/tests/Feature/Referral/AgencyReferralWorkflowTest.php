<?php

use App\Enums\ReferralStatus;
use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\MatchResult;
use App\Models\Referral;
use App\Models\User;
use App\Services\Matching\MatchingEngineService;
use App\Services\Referral\ReferralCreationService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    $this->advisorUser = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->advisorUser->id, 'is_active' => true]);
    $this->family = Family::factory()->create();
    $this->familyUser = $this->family->user;
    $this->careSeeker = $this->family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $this->lead = $this->family->leads()->create(['care_seeker_id' => $this->careSeeker->id, 'advisor_id' => $this->advisor->id, 'status' => 'assigned', 'source' => 'manual']);
    $this->agencyUser = User::factory()->create()->assignRole('agency_owner');
    $this->agency = Agency::factory()->create(['user_id' => $this->agencyUser->id, 'status' => 'published']);
    $this->referral = app(ReferralCreationService::class)->sendReferral($this->lead, $this->agency, $this->advisor, $this->advisorUser);
});

test('agency sees the referral in their inbox and can view its detail page', function () {
    $indexResponse = $this->actingAs($this->agencyUser)->get(route('agency.referrals.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee($this->careSeeker->full_name);

    $showResponse = $this->actingAs($this->agencyUser)->get(route('agency.referrals.show', $this->referral));
    $showResponse->assertOk();
});

test('CRITICAL: family contact info is not shown before the agency accepts the referral', function () {
    $response = $this->actingAs($this->agencyUser)->get(route('agency.referrals.show', $this->referral));

    $response->assertOk();
    $response->assertDontSee($this->family->user->email);
    $response->assertSee('will be available once you accept');
});

test('family contact info appears after the agency accepts', function () {
    $this->actingAs($this->agencyUser)->post(route('agency.referrals.accept', $this->referral));

    $response = $this->actingAs($this->agencyUser)->get(route('agency.referrals.show', $this->referral));

    $response->assertOk();
    $response->assertSee($this->family->user->email);
});

test('CRITICAL: agency referral pages never expose matching scores or algorithm internals', function () {
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    app(MatchingEngineService::class)->generateRecommendations($this->careSeeker);

    $response = $this->actingAs($this->agencyUser)->get(route('agency.referrals.show', $this->referral));

    $response->assertOk();
    $response->assertDontSee('compatibility_score');
    $response->assertDontSee('score_breakdown');
    $response->assertDontSee('match_results');
    $response->assertDontSee('Match Score');
    $response->assertDontSee('matching_weights');
});

test('agency can accept a referral', function () {
    $response = $this->actingAs($this->agencyUser)->post(route('agency.referrals.accept', $this->referral));

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect($this->referral->fresh()->status)->toBe(ReferralStatus::AgencyAccepted);
    Notification::assertSentTo($this->familyUser, \App\Notifications\Referral\ReferralAccepted::class);
    Notification::assertSentTo($this->advisorUser, \App\Notifications\Referral\ReferralAccepted::class);
});

test('agency can decline a referral with a reason', function () {
    $response = $this->actingAs($this->agencyUser)->post(route('agency.referrals.decline', $this->referral), [
        'reason' => 'No availability at this time',
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect($this->referral->fresh()->status)->toBe(ReferralStatus::AgencyDeclined);
    expect($this->referral->fresh()->closed_reason)->toBe('No availability at this time');
});

test('declining without a reason fails validation', function () {
    $response = $this->actingAs($this->agencyUser)->post(route('agency.referrals.decline', $this->referral), []);

    $response->assertSessionHasErrors();
    expect($this->referral->fresh()->status)->toBe(ReferralStatus::SentToAgency);
});

test('agency can request more information without changing the referral status', function () {
    $response = $this->actingAs($this->agencyUser)->post(route('agency.referrals.request-info', $this->referral), [
        'content' => 'Does the family have a budget range in mind?',
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect($this->referral->fresh()->status)->toBe(ReferralStatus::SentToAgency);
    expect($this->referral->notes()->count())->toBe(1);
});

test('agency can add a note, optionally shared with the family', function () {
    $response = $this->actingAs($this->agencyUser)->post(route('agency.referrals.notes.store', $this->referral), [
        'content' => 'We have availability starting next month.',
        'visible_to_family' => '1',
    ]);

    $response->assertRedirect();
    $note = $this->referral->notes()->latest()->first();
    expect($note->author_type)->toBe('agency');
    expect($note->visible_to_family)->toBeTrue();
});

test('an agency cannot accept a referral sent to a different agency', function () {
    $otherAgencyUser = User::factory()->create()->assignRole('agency_owner');
    Agency::factory()->create(['user_id' => $otherAgencyUser->id, 'status' => 'published']);

    $response = $this->actingAs($otherAgencyUser)->post(route('agency.referrals.accept', $this->referral));

    $response->assertStatus(403);
});

test('a family user cannot access any agency referral action', function () {
    $familyUser = User::factory()->create()->assignRole('family');

    $this->actingAs($familyUser)->get(route('agency.referrals.index'))->assertStatus(403);
    $this->actingAs($familyUser)->post(route('agency.referrals.accept', $this->referral))->assertStatus(403);
});
