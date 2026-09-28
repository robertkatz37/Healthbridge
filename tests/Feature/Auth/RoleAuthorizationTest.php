<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;

beforeEach(fn () => $this->withoutVite());

test('super_admin role is assigned to seeded admin user', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    expect($admin)->not->toBeNull();
    expect($admin->hasRole('super_admin'))->toBeTrue();
});

test('family registration assigns family role', function () {
    $this->post('/register', [
        'name'                  => 'Jane Family',
        'email'                 => 'jane@test.com',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
        'role'                  => 'family',
        'terms'                 => '1',
    ]);

    $user = User::where('email', 'jane@test.com')->first();

    expect($user->hasRole('family'))->toBeTrue();
    expect($user->hasRole('agency_owner'))->toBeFalse();
    expect($user->hasRole('super_admin'))->toBeFalse();
});

test('agency_owner registration assigns agency_owner role', function () {
    $this->post('/register', [
        'name'                  => 'Agency Co',
        'email'                 => 'agency@test.com',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
        'role'                  => 'agency_owner',
        'terms'                 => '1',
    ]);

    $user = User::where('email', 'agency@test.com')->first();

    expect($user->hasRole('agency_owner'))->toBeTrue();
    expect($user->hasRole('family'))->toBeFalse();
});

test('admin roles cannot be registered via public form', function () {
    $staffRoles = ['super_admin', 'platform_admin', 'advisor', 'moderator', 'accountant'];

    foreach ($staffRoles as $role) {
        $response = $this->post('/register', [
            'name'                  => 'Staff User',
            'email'                 => "staff_{$role}@test.com",
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => $role,
            'terms'                 => '1',
        ]);

        $response->assertSessionHasErrors('role');
    }
});

test('all 14 roles are seeded in the database', function () {
    $expectedRoles = [
        'super_admin', 'platform_admin', 'advisor_manager', 'advisor',
        'billing_manager', 'support_agent', 'content_editor', 'moderator',
        'accountant', 'agency_owner', 'agency_staff', 'family', 'reviewer', 'affiliate',
    ];

    foreach ($expectedRoles as $role) {
        $this->assertDatabaseHas('roles', ['name' => $role]);
    }
});

test('super_admin has all permissions', function () {
    $this->seed(\Database\Seeders\AdminUserSeeder::class);

    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    expect($admin->can('users.manage_all'))->toBeTrue();
    expect($admin->can('agencies.manage_all'))->toBeTrue();
    expect($admin->can('reviews.moderate'))->toBeTrue();
    expect($admin->can('billing.manage_all'))->toBeTrue();
});

test('family role has correct permissions', function () {
    $this->post('/register', [
        'name'                  => 'Test Family',
        'email'                 => 'testfam@example.com',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
        'role'                  => 'family',
        'terms'                 => '1',
    ]);

    $user = User::where('email', 'testfam@example.com')->first();

    expect($user->can('care_seekers.manage_own'))->toBeTrue();
    expect($user->can('reviews.submit'))->toBeTrue();
    expect($user->can('users.manage_all'))->toBeFalse();
    expect($user->can('agencies.manage_all'))->toBeFalse();
});
