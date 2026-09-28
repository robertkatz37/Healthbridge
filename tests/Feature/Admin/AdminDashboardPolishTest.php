<?php

use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

test('super_admin can view the Advisors list', function () {
    $advisorUser = User::factory()->create()->assignRole('advisor');
    Advisor::create(['user_id' => $advisorUser->id, 'is_active' => true]);

    $response = $this->actingAs($this->admin)->get(route('admin.advisors.index'));

    $response->assertOk();
    $response->assertSee($advisorUser->name);
});

test('super_admin can view a single Advisor detail page', function () {
    $advisorUser = User::factory()->create()->assignRole('advisor');
    $advisor = Advisor::create(['user_id' => $advisorUser->id, 'is_active' => true]);

    $response = $this->actingAs($this->admin)->get(route('admin.advisors.show', $advisor));

    $response->assertOk();
});

test('super_admin can view the Families list', function () {
    $family = Family::factory()->create();

    $response = $this->actingAs($this->admin)->get(route('admin.families.index'));

    $response->assertOk();
    $response->assertSee($family->user->name);
});

test('super_admin can view a single Family detail page', function () {
    $family = Family::factory()->create();
    $family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);

    $response = $this->actingAs($this->admin)->get(route('admin.families.show', $family));

    $response->assertOk();
    $response->assertSee('Test Seeker');
});

test('super_admin can view the Audit Log', function () {
    $agency = Agency::factory()->create();
    activity()->causedBy($this->admin)->performedOn($agency)->log('Test audit entry');

    $response = $this->actingAs($this->admin)->get(route('admin.activity-log.index'));

    $response->assertOk();
    $response->assertSee('Test audit entry');
});

test('super_admin can view Reports and Analytics with real computed data', function () {
    Agency::factory()->count(3)->create(['status' => 'published']);
    Family::factory()->count(2)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $summary = $response->viewData('summary');
    expect($summary['total_agencies'])->toBeGreaterThanOrEqual(3);
    expect($summary['total_families'])->toBeGreaterThanOrEqual(2);
});

test('non-admin roles cannot access Advisors, Families, Audit Log, or Reports', function () {
    $family = User::factory()->create()->assignRole('family');
    $agency = User::factory()->create()->assignRole('agency_owner');

    foreach ([$family, $agency] as $user) {
        $this->actingAs($user)->get(route('admin.advisors.index'))->assertStatus(403);
        $this->actingAs($user)->get(route('admin.families.index'))->assertStatus(403);
        $this->actingAs($user)->get(route('admin.activity-log.index'))->assertStatus(403);
        $this->actingAs($user)->get(route('admin.reports.index'))->assertStatus(403);
    }
});

test('the admin sidebar contains real, functional links for every checklist item, with no disabled Phase 19 placeholders for System Health or Audit Logs', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Advisors');
    $response->assertSee('Families');
    $response->assertSee('Matching Engine');
    $response->assertSee('Audit Logs');
    $response->assertSee('System Health');
    $response->assertSee('Reports & Analytics', false);
    // The old disabled placeholder pattern for these two specifically
    // should no longer exist.
    $response->assertDontSee('System Health</span>
                    <span class="hb-nav-badge">Phase', false);
});
