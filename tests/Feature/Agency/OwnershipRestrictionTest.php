<?php

use App\Models\AgencyCategory;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->category = AgencyCategory::first();
});

test('super_admin cannot access the agency registration wizard', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $response = $this->actingAs($admin)->get(route('agency.register.step1'));

    $response->assertStatus(403);
});

test('super_admin cannot submit the agency registration wizard step 1', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $response = $this->actingAs($admin)->post(route('agency.register.step1.store'), [
        'name' => 'Should Not Be Created',
        'agency_category_id' => $this->category->id,
    ]);

    $response->assertStatus(403);
    $this->assertDatabaseMissing('agencies', ['name' => 'Should Not Be Created']);
});

test('platform_admin cannot access the agency registration wizard', function () {
    $admin = User::factory()->create()->assignRole('platform_admin');

    $response = $this->actingAs($admin)->get(route('agency.register.step1'));

    $response->assertStatus(403);
});

test('super_admin cannot access the agency owner dashboard', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $response = $this->actingAs($admin)->get(route('agency.dashboard'));

    $response->assertStatus(403);
});

test('platform_admin cannot access agency profile management', function () {
    $admin = User::factory()->create()->assignRole('platform_admin');

    $response = $this->actingAs($admin)->get(route('agency.profile.edit'));

    $response->assertStatus(403);
});

test('agency_owner can still access the wizard normally', function () {
    $owner = User::factory()->create()->assignRole('agency_owner');

    $response = $this->actingAs($owner)->get(route('agency.register.step1'));

    $response->assertOk();
});

test('agency_staff can still access the agency dashboard normally', function () {
    $owner = User::factory()->create()->assignRole('agency_owner');
    $agency = \App\Models\Agency::factory()->create([
        'user_id' => $owner->id,
        'onboarding_completed_at' => now(),
    ]);
    $staff = User::factory()->create()->assignRole('agency_staff');
    \App\Models\AgencyStaff::create(['agency_id' => $agency->id, 'user_id' => $staff->id]);

    $response = $this->actingAs($staff)->get(route('agency.dashboard'));

    $response->assertOk();
});

test('super_admin retains agencies.manage_all and agencies.moderate permissions for future admin functionality', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    expect($admin->can('agencies.manage_all'))->toBeTrue();
    expect($admin->can('agencies.moderate'))->toBeTrue();
});
