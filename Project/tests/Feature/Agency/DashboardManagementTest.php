<?php

use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;

beforeEach(function () {
    $this->withoutVite();
    $this->owner = User::factory()->create();
    $this->owner->assignRole('agency_owner');
    $this->category = AgencyCategory::first();

    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, [
        'agency_category_id' => $this->category->id,
        'name' => 'Test Agency',
    ]);
    $this->agency->update([
        'status' => 'published',
        'onboarding_completed_at' => now(),
    ]);
});

test('agency owner is redirected to onboarding if not yet complete', function () {
    $incompleteOwner = User::factory()->create()->assignRole('agency_owner');
    app(AgencyProvisioningService::class)->createDraftAgency($incompleteOwner, [
        'agency_category_id' => $this->category->id,
        'name' => 'Incomplete Agency',
    ]);

    $response = $this->actingAs($incompleteOwner)->get(route('agency.dashboard'));

    $response->assertRedirect(route('agency.register.step2'));
});

test('agency owner with completed onboarding sees the dashboard', function () {
    $response = $this->actingAs($this->owner)->get(route('agency.dashboard'));

    $response->assertOk();
    $response->assertSee('Test Agency');
});

test('agency owner can update their profile', function () {
    $response = $this->actingAs($this->owner)->put(route('agency.profile.update'), [
        'name' => 'Updated Agency Name',
        'agency_category_id' => $this->category->id,
        'description' => 'New description',
    ]);

    $response->assertRedirect();
    expect($this->agency->fresh()->name)->toBe('Updated Agency Name');
});

test('agency owner cannot update another agency they do not own', function () {
    $otherOwner = User::factory()->create()->assignRole('agency_owner');
    $otherAgency = Agency::factory()->create(['user_id' => $otherOwner->id]);

    // Attempt via the other owner's session against their own route context
    // (the route always resolves currentAgency() from the authenticated user,
    // so this test verifies isolation rather than direct object tampering)
    $response = $this->actingAs($otherOwner)->put(route('agency.profile.update'), [
        'name' => 'Hijacked Name',
        'agency_category_id' => $this->category->id,
    ]);

    $response->assertRedirect();
    expect($this->agency->fresh()->name)->toBe('Test Agency'); // untouched
    expect($otherAgency->fresh()->name)->toBe('Hijacked Name'); // only their own changed
});

test('agency staff can access the dashboard for their agency', function () {
    $staffUser = User::factory()->create()->assignRole('agency_staff');
    \App\Models\AgencyStaff::create([
        'agency_id' => $this->agency->id,
        'user_id' => $staffUser->id,
        'job_title' => 'Coordinator',
    ]);

    $response = $this->actingAs($staffUser)->get(route('agency.dashboard'));

    $response->assertOk();
    $response->assertSee('Test Agency');
});

test('family user cannot access agency dashboard routes', function () {
    $family = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($family)->get(route('agency.dashboard'));

    $response->assertStatus(403);
});
