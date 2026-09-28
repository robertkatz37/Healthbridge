<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Spatie\Permission\Models\Role;

beforeEach(fn () => $this->withoutVite());

test('super_admin can access user management', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
    $response->assertSee('User Management');
});

test('family user cannot access user management', function () {
    $user = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($user)->get(route('admin.users.index'));

    $response->assertStatus(403);
});

test('admin can view a specific user', function () {
    $this->seed(AdminUserSeeder::class);
    $admin  = User::where('email', 'admin@healthsbridge.test')->first();
    $target = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.users.show', $target));

    $response->assertOk();
    $response->assertSee($target->name);
});

test('admin can update user roles', function () {
    $this->seed(AdminUserSeeder::class);
    $admin  = User::where('email', 'admin@healthsbridge.test')->first();
    $target = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($admin)->put(
        route('admin.users.roles.update', $target),
        ['roles' => ['agency_owner']]
    );

    $response->assertRedirect();
    $target->refresh();
    expect($target->hasRole('agency_owner'))->toBeTrue();
    expect($target->hasRole('family'))->toBeFalse();
});

test('cannot remove super_admin role from own account', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $response = $this->actingAs($admin)->put(
        route('admin.users.roles.update', $admin),
        ['roles' => ['family']] // trying to demote self
    );

    $response->assertSessionHasErrors('roles');
    expect($admin->fresh()->hasRole('super_admin'))->toBeTrue();
});

test('family user cannot update roles of any user', function () {
    $family = User::factory()->create()->assignRole('family');
    $target = User::factory()->create()->assignRole('agency_owner');

    $response = $this->actingAs($family)->put(
        route('admin.users.roles.update', $target),
        ['roles' => ['super_admin']]
    );

    $response->assertStatus(403);
});

test('admin user list is searchable', function () {
    $this->seed(AdminUserSeeder::class);
    $admin    = User::where('email', 'admin@healthsbridge.test')->first();
    $specific = User::factory()->create(['name' => 'Unique Test Person', 'email' => 'uniqueperson@example.com']);

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Unique Test Person']));

    $response->assertOk();
    $response->assertSee('Unique Test Person');
});

test('admin user list is filterable by role', function () {
    $this->seed(AdminUserSeeder::class);
    $admin  = User::where('email', 'admin@healthsbridge.test')->first();
    User::factory()->create()->assignRole('advisor');

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'advisor']));

    $response->assertOk();
});

test('403 page renders for unauthorized access', function () {
    $user = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertStatus(403);
});
