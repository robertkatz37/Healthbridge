<?php

use App\Enums\ReferralStatus;
use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\User;
use App\Services\Referral\ReferralCreationService;
use App\Services\Referral\ReferralPipelineService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    $this->advisorUser = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->advisorUser->id, 'is_active' => true]);
    $this->family = Family::factory()->create();
    $this->careSeeker = $this->family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $this->lead = $this->family->leads()->create(['care_seeker_id' => $this->careSeeker->id, 'advisor_id' => $this->advisor->id, 'status' => 'assigned', 'source' => 'manual']);
    $this->agencyUser = User::factory()->create()->assignRole('agency_owner');
    $this->agency = Agency::factory()->create(['user_id' => $this->agencyUser->id, 'status' => 'published']);
});

test('advisor dashboard shows Referral KPIs including conversion rate', function () {
    app(ReferralCreationService::class)->sendReferral($this->lead, $this->agency, $this->advisor, $this->advisorUser);

    $response = $this->actingAs($this->advisorUser)->get(route('advisor.dashboard'));

    $response->assertOk();
    $response->assertSee('Total Referrals');
    $response->assertSee('Referral Conversion Rate');
    $referralKpis = $response->viewData('referralKpis');
    expect($referralKpis['total'])->toBe(1);
    expect($referralKpis['pending'])->toBe(1);
});

test('advisor dashboard shows Pending Follow-ups', function () {
    $referral = app(ReferralCreationService::class)->sendReferral($this->lead, $this->agency, $this->advisor, $this->advisorUser);
    app(ReferralPipelineService::class)->transition($referral, ReferralStatus::AgencyAccepted, $this->agencyUser);
    app(ReferralPipelineService::class)->transition($referral, ReferralStatus::TourScheduled, $this->advisorUser);
    // Manually advance through tour completed without a real tour object for this KPI-focused test
    $referral->update(['status' => ReferralStatus::TourCompleted->value]);
    app(ReferralPipelineService::class)->transition($referral, ReferralStatus::FollowUpRequired, $this->advisorUser);

    $response = $this->actingAs($this->advisorUser)->get(route('advisor.dashboard'));

    $response->assertOk();
    $response->assertSee('Pending Follow-ups');
    $response->assertSee($this->agency->name);
});

test('agency dashboard shows Pending Actions for incoming referrals', function () {
    app(ReferralCreationService::class)->sendReferral($this->lead, $this->agency, $this->advisor, $this->advisorUser);
    $this->agency->update(['onboarding_completed_at' => now()]); // ensure onboarding complete for dashboard access

    $response = $this->actingAs($this->agencyUser)->get(route('agency.dashboard'));

    $response->assertOk();
    $response->assertSee('Pending Actions');
});

test('agency dashboard shows Upcoming Tours', function () {
    $referral = app(ReferralCreationService::class)->sendReferral($this->lead, $this->agency, $this->advisor, $this->advisorUser);
    app(ReferralPipelineService::class)->transition($referral, ReferralStatus::AgencyAccepted, $this->agencyUser);
    app(\App\Services\Referral\TourManagementService::class)->schedule($referral, now()->addDays(4)->toDateString(), null, null, $this->advisorUser);
    $this->agency->update(['onboarding_completed_at' => now()]);

    $response = $this->actingAs($this->agencyUser)->get(route('agency.dashboard'));

    $response->assertOk();
    $response->assertSee('Upcoming Tours');
});
