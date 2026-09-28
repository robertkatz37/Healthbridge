<?php

use App\Models\Advisor;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create()->assignRole('advisor');
});

test('advisor dashboard self-heals a missing Advisor row', function () {
    // Mirrors the Family EnsureFamilyRecordExists fix (Phase 9) — an
    // advisor-role account with no Advisor row (e.g. role assigned via
    // Admin Panel) must not redirect-loop.
    expect(Advisor::where('user_id', $this->user->id)->exists())->toBeFalse();

    $response = $this->actingAs($this->user)->get(route('advisor.dashboard'));

    $response->assertOk();
    expect(Advisor::where('user_id', $this->user->id)->exists())->toBeTrue();
});

test('advisor dashboard shows KPI summary', function () {
    $advisor = Advisor::create(['user_id' => $this->user->id, 'is_active' => true]);
    $advisor->leads()->create(['family_id' => \App\Models\Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.dashboard'));

    $response->assertOk();
});

test('advisor manager sees a team summary when they have team members', function () {
    $managerUser = User::factory()->create()->assignRole('advisor_manager');
    $manager = Advisor::create(['user_id' => $managerUser->id, 'is_active' => true]);
    $teamMemberUser = User::factory()->create()->assignRole('advisor');
    Advisor::create(['user_id' => $teamMemberUser->id, 'is_active' => true, 'advisor_manager_id' => $manager->id]);

    $response = $this->actingAs($managerUser)->get(route('advisor.dashboard'));

    $response->assertOk();
    $response->assertSee('Team Overview');
});

test('agency_owner cannot access advisor dashboard', function () {
    $owner = User::factory()->create()->assignRole('agency_owner');

    $response = $this->actingAs($owner)->get(route('advisor.dashboard'));

    $response->assertStatus(403);
});

test('family cannot access advisor dashboard', function () {
    $family = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($family)->get(route('advisor.dashboard'));

    $response->assertStatus(403);
});

test('super_admin cannot access advisor-facing routes directly', function () {
    $admin = User::factory()->create()->assignRole('super_admin');

    $response = $this->actingAs($admin)->get(route('advisor.dashboard'));

    $response->assertStatus(403);
});

// ─── Territories ────────────────────────────────────────────────────────────

test('advisor can add a territory', function () {
    Advisor::create(['user_id' => $this->user->id, 'is_active' => true]);

    $response = $this->actingAs($this->user)->post(route('advisor.territories.store'), [
        'city' => 'Austin', 'state' => 'TX', 'radius_miles' => 25,
    ]);

    $response->assertRedirect();
    expect(Advisor::where('user_id', $this->user->id)->first()->territories()->count())->toBe(1);
});

test('advisor can delete their own territory', function () {
    $advisor = Advisor::create(['user_id' => $this->user->id, 'is_active' => true]);
    $territory = $advisor->territories()->create(['city' => 'Austin', 'state' => 'TX']);

    $response = $this->actingAs($this->user)->delete(route('advisor.territories.destroy', $territory));

    $response->assertRedirect();
    expect($advisor->territories()->count())->toBe(0);
});

test('advisor cannot delete another advisors territory', function () {
    Advisor::create(['user_id' => $this->user->id, 'is_active' => true]);
    $otherAdvisor = Advisor::factory()->create();
    $otherTerritory = $otherAdvisor->territories()->create(['city' => 'Austin', 'state' => 'TX']);

    $response = $this->actingAs($this->user)->delete(route('advisor.territories.destroy', $otherTerritory));

    $response->assertStatus(403);
});
