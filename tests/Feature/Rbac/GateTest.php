<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(fn () => $this->withoutVite());

test('super_admin can view admin panel', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $this->actingAs($admin);

    expect(Gate::allows('view-admin-panel'))->toBeTrue();
});

test('family user cannot view admin panel', function () {
    $user = User::factory()->create()->assignRole('family');

    $this->actingAs($user);

    expect(Gate::allows('view-admin-panel'))->toBeFalse();
});

test('platform_admin can view admin panel', function () {
    $user = User::factory()->create()->assignRole('platform_admin');

    $this->actingAs($user);

    expect(Gate::allows('view-admin-panel'))->toBeTrue();
});

test('content_editor can view admin panel', function () {
    $user = User::factory()->create()->assignRole('content_editor');

    $this->actingAs($user);

    expect(Gate::allows('view-admin-panel'))->toBeTrue();
});

test('moderator can view admin panel', function () {
    $user = User::factory()->create()->assignRole('moderator');

    $this->actingAs($user);

    expect(Gate::allows('view-admin-panel'))->toBeTrue();
});

test('super_admin can manage platform settings', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $this->actingAs($admin);

    expect(Gate::allows('manage-platform-settings'))->toBeTrue();
});

test('agency_owner cannot manage platform settings', function () {
    $user = User::factory()->create()->assignRole('agency_owner');

    $this->actingAs($user);

    expect(Gate::allows('manage-platform-settings'))->toBeFalse();
});

test('super_admin can access advisor crm gate', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $this->actingAs($admin);

    expect(Gate::allows('access-advisor-crm'))->toBeTrue();
});

test('advisor can access advisor crm gate', function () {
    $user = User::factory()->create()->assignRole('advisor');

    $this->actingAs($user);

    expect(Gate::allows('access-advisor-crm'))->toBeTrue();
});

test('family user cannot access advisor crm gate', function () {
    $user = User::factory()->create()->assignRole('family');

    $this->actingAs($user);

    expect(Gate::allows('access-advisor-crm'))->toBeFalse();
});

test('only super_admin can impersonate users', function () {
    $this->seed(AdminUserSeeder::class);
    $admin  = User::where('email', 'admin@healthsbridge.test')->first();
    $family = User::factory()->create()->assignRole('family');

    $this->actingAs($admin);
    expect(Gate::allows('impersonate-users'))->toBeTrue();

    $this->actingAs($family);
    expect(Gate::allows('impersonate-users'))->toBeFalse();
});
