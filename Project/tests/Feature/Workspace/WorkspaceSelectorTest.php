<?php

use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\Family;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;

beforeEach(fn () => $this->withoutVite());

test('single-workspace user is redirected straight to their dashboard, no selector shown', function () {
    $family = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($family)->get(route('dashboard'));

    $response->assertRedirect(route('family.dashboard'));
});

test('multi-workspace user is redirected to the workspace selector on first login', function () {
    $user = User::factory()->create();
    $user->assignRole(['family', 'agency_owner']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('workspace.select'));
});

test('workspace selector lists every workspace the user has access to', function () {
    $user = User::factory()->create();
    $user->assignRole(['family', 'agency_owner']);

    $response = $this->actingAs($user)->get(route('workspace.select'));

    $response->assertOk();
    $response->assertSee('Family Dashboard');
    $response->assertSee('Agency Dashboard');
});

test('visiting the selector with only one workspace redirects to dashboard instead', function () {
    $family = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($family)->get(route('workspace.select'));

    $response->assertRedirect(route('dashboard'));
});

test('user can enter a workspace they have access to', function () {
    $user = User::factory()->create();
    $user->assignRole(['family', 'agency_owner']);

    $response = $this->actingAs($user)->post(route('workspace.enter', 'family'));

    $response->assertRedirect(route('family.dashboard'));
});

test('user cannot enter a workspace they do not have access to', function () {
    $family = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($family)->post(route('workspace.enter', 'admin'));

    $response->assertStatus(403);
});

test('entering a workspace is remembered in session for subsequent dashboard visits', function () {
    $user = User::factory()->create();
    $user->assignRole(['family', 'agency_owner']);

    $this->actingAs($user)->post(route('workspace.enter', 'agency'));

    // Subsequent visit to the generic dashboard should skip the selector
    // and go straight to the remembered workspace.
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('agency.dashboard'));
});

test('remembered workspace is discarded if the role granting it is later revoked', function () {
    $user = User::factory()->create();
    $user->assignRole(['family', 'agency_owner']);

    $this->actingAs($user)->post(route('workspace.enter', 'agency'));

    $user->removeRole('agency_owner');

    $response = $this->actingAs($user)->get(route('dashboard'));

    // Only 'family' remains, so it should redirect straight there (not
    // to the now-invalid remembered 'agency' workspace, and not to the
    // selector either, since only one workspace remains).
    $response->assertRedirect(route('family.dashboard'));
});

test('entering a workspace is logged in activity log', function () {
    $user = User::factory()->create();
    $user->assignRole(['family', 'agency_owner']);

    $this->actingAs($user)->post(route('workspace.enter', 'family'));

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $user->id,
        'description' => 'Workspace selected',
    ]);
});

test('admin roles that all map to the same workspace count as a single workspace', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();
    // super_admin already maps to 'admin' workspace; adding another
    // admin-mapped role should NOT create a second selectable workspace.
    $admin->assignRole('moderator');

    $response = $this->actingAs($admin)->get(route('dashboard'));

    // Still redirects straight through — no selector needed for a user
    // whose multiple roles all resolve to the same single workspace.
    $response->assertRedirect(route('admin.dashboard'));
});

test('three or more workspaces all appear in the selector', function () {
    $user = User::factory()->create();
    $user->assignRole(['family', 'agency_owner', 'advisor']);

    $response = $this->actingAs($user)->get(route('workspace.select'));

    $response->assertOk();
    $response->assertSee('Family Dashboard');
    $response->assertSee('Agency Dashboard');
    $response->assertSee('Advisor CRM');
});
