<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;

beforeEach(fn () => $this->withoutVite());

test('super_admin is redirected to admin dashboard', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertRedirect(route('admin.dashboard'));
});

test('platform_admin is redirected to admin dashboard', function () {
    $user = User::factory()->create()->assignRole('platform_admin');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('admin.dashboard'));
});

test('advisor is redirected to advisor dashboard', function () {
    $user = User::factory()->create()->assignRole('advisor');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('advisor.dashboard'));
});

test('advisor_manager is redirected to advisor dashboard', function () {
    $user = User::factory()->create()->assignRole('advisor_manager');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('advisor.dashboard'));
});

test('agency_owner is redirected to agency dashboard', function () {
    $user = User::factory()->create()->assignRole('agency_owner');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('agency.dashboard'));
});

test('family user is redirected to family dashboard', function () {
    $user = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('family.dashboard'));
});

test('admin dashboard is accessible by super_admin', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
});

test('admin dashboard is not accessible by family user', function () {
    $user = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertStatus(403);
});

test('advisor dashboard is not accessible by family user', function () {
    $user = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($user)->get(route('advisor.dashboard'));

    $response->assertStatus(403);
});

test('family dashboard is accessible by family user', function () {
    $user = User::factory()->create()->assignRole('family');
    // Real registration (RegisteredUserController) always creates a Family
    // row for the 'family' role — the factory shortcut used in this test
    // doesn't, so it's created explicitly here to match realistic setup
    // (Family\DashboardController correctly requires one to exist, per
    // Phase 9's ownership checks).
    \App\Models\Family::create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('family.dashboard'));

    $response->assertOk();
});

test('family dashboard is not accessible by agency_owner', function () {
    $user = User::factory()->create()->assignRole('agency_owner');

    $response = $this->actingAs($user)->get(route('family.dashboard'));

    $response->assertStatus(403);
});

test('guest is redirected to login when accessing protected routes', function () {
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('login'));

    $response = $this->get(route('advisor.dashboard'));
    $response->assertRedirect(route('login'));

    $response = $this->get(route('family.dashboard'));
    $response->assertRedirect(route('login'));
});
