<?php

use App\Enums\ReferralStatus;
use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\Referral;
use App\Models\User;
use App\Services\Referral\ReferralCreationService;
use App\Services\Referral\ReferralPipelineService;
use App\Services\Referral\TourManagementService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    $this->advisorUser = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->advisorUser->id, 'is_active' => true]);
    $this->family = Family::factory()->create();
    $this->familyUser = $this->family->user;
    $this->familyUser->assignRole('family');
    $this->careSeeker = $this->family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $this->lead = $this->family->leads()->create(['care_seeker_id' => $this->careSeeker->id, 'advisor_id' => $this->advisor->id, 'status' => 'assigned', 'source' => 'manual']);
    $this->agencyUser = User::factory()->create()->assignRole('agency_owner');
    $this->agency = Agency::factory()->create(['user_id' => $this->agencyUser->id, 'status' => 'published']);
    $this->referral = app(ReferralCreationService::class)->sendReferral($this->lead, $this->agency, $this->advisor, $this->advisorUser);
});

test('family can view their referrals list and a single referral detail page', function () {
    $indexResponse = $this->actingAs($this->familyUser)->get(route('family.referrals.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee($this->agency->name);

    $showResponse = $this->actingAs($this->familyUser)->get(route('family.referrals.show', $this->referral));
    $showResponse->assertOk();
});

test('family sees an advisor note only when it is explicitly marked visible to family', function () {
    $this->referral->notes()->create(['author_id' => $this->advisorUser->id, 'author_type' => 'advisor', 'visible_to_family' => true, 'content' => 'Great fit, moving forward.']);
    $this->referral->notes()->create(['author_id' => $this->advisorUser->id, 'author_type' => 'advisor', 'visible_to_family' => false, 'content' => 'Internal-only note about pricing negotiation.']);

    $response = $this->actingAs($this->familyUser)->get(route('family.referrals.show', $this->referral));

    $response->assertOk();
    $response->assertSee('Great fit, moving forward.');
    $response->assertDontSee('Internal-only note about pricing negotiation.');
});

test('family can view and cancel their own upcoming tour', function () {
    app(ReferralPipelineService::class)->transition($this->referral, ReferralStatus::AgencyAccepted, $this->agencyUser);
    $tour = app(TourManagementService::class)->schedule($this->referral, now()->addDays(5)->toDateString(), '2-4 PM', null, $this->advisorUser);

    $indexResponse = $this->actingAs($this->familyUser)->get(route('family.tours.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee($this->agency->name);

    $cancelResponse = $this->actingAs($this->familyUser)->post(route('family.tours.cancel', $tour));
    $cancelResponse->assertRedirect();
    $cancelResponse->assertSessionDoesntHaveErrors();
    expect($tour->fresh()->status->value)->toBe('cancelled');
});

test('a family cannot view another familys referral', function () {
    $otherFamilyUser = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($otherFamilyUser)->get(route('family.referrals.show', $this->referral));

    $response->assertStatus(403);
});

test('CRITICAL: family referral pages never expose matching scores', function () {
    $response = $this->actingAs($this->familyUser)->get(route('family.referrals.show', $this->referral));

    $response->assertOk();
    $response->assertDontSee('compatibility_score');
    $response->assertDontSee('score_breakdown');
});
