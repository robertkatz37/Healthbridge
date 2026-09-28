<?php

use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\User;
use App\Services\Referral\ReferralCreationService;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();

    $advisorUser = User::factory()->create()->assignRole('advisor');
    $advisor = Advisor::create(['user_id' => $advisorUser->id, 'is_active' => true]);
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $lead = $family->leads()->create(['care_seeker_id' => $careSeeker->id, 'advisor_id' => $advisor->id, 'status' => 'assigned', 'source' => 'manual']);
    $agency = Agency::factory()->create(['status' => 'published']);
    $this->referral = app(ReferralCreationService::class)->sendReferral($lead, $agency, $advisor, $advisorUser);
});

test('super_admin can view the referrals list', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.referrals.index'));

    $response->assertOk();
    $response->assertSee($this->referral->agency->name);
});

test('super_admin can view a single referral detail page with full audit history', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.referrals.show', $this->referral));

    $response->assertOk();
    $response->assertSee('Audit History');
    $response->assertSee('Sent to Agency');
});

test('super_admin can filter referrals by status', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.referrals.index', ['status' => 'sent_to_agency']));

    $response->assertOk();
    $response->assertSee($this->referral->agency->name);
});

test('non-admin roles cannot access referral oversight', function () {
    $advisorUser = User::factory()->create()->assignRole('advisor');
    $familyUser = User::factory()->create()->assignRole('family');

    $this->actingAs($advisorUser)->get(route('admin.referrals.index'))->assertStatus(403);
    $this->actingAs($familyUser)->get(route('admin.referrals.index'))->assertStatus(403);
});

test('admin reports page shows real Referral Analytics data', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertSee('Referrals by Status');
    $response->assertSee('Referral Conversion Funnel');
    $response->assertSee('Recent Referral Activity');
    $response->assertSee($this->referral->agency->name);

    $referralSummary = $response->viewData('referralSummary');
    expect($referralSummary['total_referrals'])->toBeGreaterThanOrEqual(1);
});

test('the admin sidebar links to Referrals', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Referrals');
});
