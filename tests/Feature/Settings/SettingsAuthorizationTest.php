<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;

beforeEach(fn () => $this->withoutVite());

test('super_admin can access settings pages', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $response = $this->actingAs($admin)->get(route('admin.settings.general.edit'));

    $response->assertOk();
});

test('platform_admin cannot access settings pages', function () {
    $admin = User::factory()->create()->assignRole('platform_admin');

    $response = $this->actingAs($admin)->get(route('admin.settings.general.edit'));

    $response->assertStatus(403);
});

test('moderator cannot access settings pages', function () {
    $moderator = User::factory()->create()->assignRole('moderator');

    $response = $this->actingAs($moderator)->get(route('admin.settings.general.edit'));

    $response->assertStatus(403);
});

test('agency_owner cannot access settings pages', function () {
    $owner = User::factory()->create()->assignRole('agency_owner');

    $response = $this->actingAs($owner)->get(route('admin.settings.general.edit'));

    $response->assertStatus(403);
});

test('family user cannot access settings pages', function () {
    $family = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($family)->get(route('admin.settings.general.edit'));

    $response->assertStatus(403);
});

test('guest is redirected to login', function () {
    $response = $this->get(route('admin.settings.general.edit'));

    $response->assertRedirect(route('login'));
});

test('every settings sub-page is protected the same way', function () {
    $moderator = User::factory()->create()->assignRole('moderator');

    $routes = [
        route('admin.settings.mail.edit'),
        route('admin.settings.branding.edit'),
        route('admin.settings.templates.index'),
        route('admin.settings.email-logs.index'),
        route('admin.settings.failed-emails.index'),
        route('admin.settings.system.show'),
    ];

    foreach ($routes as $url) {
        $this->actingAs($moderator)->get($url)->assertStatus(403);
    }
});

test('settings index redirects to general settings', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $response = $this->actingAs($admin)->get(route('admin.settings.index'));

    $response->assertRedirect(route('admin.settings.general.edit'));
});
