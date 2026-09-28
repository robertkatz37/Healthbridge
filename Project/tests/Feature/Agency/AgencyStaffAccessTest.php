<?php

use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\AgencyStaff;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $category = AgencyCategory::first();

    $this->owner = User::factory()->create()->assignRole('agency_owner');
    $this->agency = Agency::factory()->create([
        'user_id' => $this->owner->id,
        'agency_category_id' => $category->id,
        'status' => 'published',
        'onboarding_completed_at' => now(),
    ]);

    $this->staffUser = User::factory()->create()->assignRole('agency_staff');
    AgencyStaff::create(['agency_id' => $this->agency->id, 'user_id' => $this->staffUser->id]);
});

test('agency staff can view the agency dashboard they belong to', function () {
    $response = $this->actingAs($this->staffUser)->get(route('agency.dashboard'));

    $response->assertOk();
});

test('agency staff can manage services for their agency', function () {
    $response = $this->actingAs($this->staffUser)->post(route('agency.services.store'), [
        'name' => 'Added by staff',
    ]);

    $response->assertRedirect();
    expect($this->agency->services()->count())->toBe(1);
});

test('a user with no agency association sees no dashboard', function () {
    $unaffiliated = User::factory()->create()->assignRole('agency_staff');

    $response = $this->actingAs($unaffiliated)->get(route('agency.dashboard'));

    $response->assertRedirect(route('agency.register.step1'));
});

test('staff from one agency cannot manage a different agencys resources', function () {
    $otherAgency = Agency::factory()->create();
    $otherService = $otherAgency->services()->create(['name' => 'Not staffs agency']);

    $response = $this->actingAs($this->staffUser)->delete(route('agency.services.destroy', $otherService));

    $response->assertStatus(403);
});
