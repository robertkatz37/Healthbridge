<?php

use App\Enums\ReferralStatus;
use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
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
});

test('family dashboard shows a referral status card once a referral exists', function () {
    app(ReferralCreationService::class)->sendReferral($this->lead, $this->agency, $this->advisor, $this->advisorUser);

    $response = $this->actingAs($this->familyUser)->get(route('family.dashboard'));

    $response->assertOk();
    $response->assertSee('Referral Status');
    $response->assertSee($this->agency->name);
    $response->assertSee('Sent to Agency');
});

test('family dashboard shows upcoming tours', function () {
    $referral = app(ReferralCreationService::class)->sendReferral($this->lead, $this->agency, $this->advisor, $this->advisorUser);
    app(ReferralPipelineService::class)->transition($referral, ReferralStatus::AgencyAccepted, $this->agencyUser);
    app(TourManagementService::class)->schedule($referral, now()->addDays(3)->toDateString(), '9-11 AM', null, $this->advisorUser);

    $response = $this->actingAs($this->familyUser)->get(route('family.dashboard'));

    $response->assertOk();
    $response->assertSee('Upcoming Tours');
    $response->assertSee($this->agency->name);
});

test('family dashboard shows the referral timeline', function () {
    app(ReferralCreationService::class)->sendReferral($this->lead, $this->agency, $this->advisor, $this->advisorUser);

    $response = $this->actingAs($this->familyUser)->get(route('family.dashboard'));

    $response->assertOk();
    $response->assertSee('Referral Timeline');
});

test('family dashboard shows a link to message the assigned advisor, and the link actually works', function () {
    app(ReferralCreationService::class)->sendReferral($this->lead, $this->agency, $this->advisor, $this->advisorUser);

    $dashboardResponse = $this->actingAs($this->familyUser)->get(route('family.dashboard'));
    $dashboardResponse->assertOk();
    $dashboardResponse->assertSee('Message ' . $this->advisorUser->name, false);

    // The link itself must resolve to a real, working page - not a dead link.
    $conversationResponse = $this->actingAs($this->familyUser)->get(route('family.leads.conversation', $this->lead));
    $conversationResponse->assertOk();
});

test('family can send a message to their advisor and the advisor receives it in their own conversation view', function () {
    $response = $this->actingAs($this->familyUser)->post(route('family.leads.conversation.store', $this->lead), [
        'body' => 'What time works best for a tour next week?',
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();

    $advisorResponse = $this->actingAs($this->advisorUser)->get(route('advisor.leads.conversation', $this->lead));
    $advisorResponse->assertOk();
    $advisorResponse->assertSee('What time works best for a tour next week?');
});

test('a family cannot message on a lead that is not theirs', function () {
    $otherFamilyUser = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($otherFamilyUser)->post(route('family.leads.conversation.store', $this->lead), [
        'body' => 'Trying to snoop',
    ]);

    $response->assertStatus(403);
});
