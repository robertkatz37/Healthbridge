<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

test('admin can update general settings', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.settings.general.update'), [
        'platform_name' => 'Updated Platform Name',
        'support_email' => 'new-support@example.com',
        'support_phone' => '555-0100',
        'timezone' => 'America/New_York',
        'maintenance_mode' => '1',
    ]);

    $response->assertRedirect();

    $settings = app(\App\Services\Settings\SettingsService::class);
    expect($settings->get('platform_name'))->toBe('Updated Platform Name');
    expect($settings->get('support_email'))->toBe('new-support@example.com');
    expect($settings->get('maintenance_mode'))->toBeTrue();
});

test('turning maintenance mode off is correctly persisted as false', function () {
    // Regression coverage for the boolean-normalization bug — omitting
    // the checkbox from the request (unchecked) must store false, not
    // silently leave the old value or invert it.
    $this->actingAs($this->admin)->put(route('admin.settings.general.update'), [
        'platform_name' => 'Test', 'support_email' => 'a@b.com', 'timezone' => 'UTC',
        'maintenance_mode' => '1',
    ]);
    expect(app(\App\Services\Settings\SettingsService::class)->get('maintenance_mode'))->toBeTrue();

    $this->actingAs($this->admin)->put(route('admin.settings.general.update'), [
        'platform_name' => 'Test', 'support_email' => 'a@b.com', 'timezone' => 'UTC',
        // maintenance_mode omitted = unchecked checkbox
    ]);
    expect(app(\App\Services\Settings\SettingsService::class)->get('maintenance_mode'))->toBeFalse();
});

test('general settings requires a valid email and timezone', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.settings.general.update'), [
        'platform_name' => 'Test',
        'support_email' => 'not-an-email',
        'timezone' => 'Not/A/Real/Timezone',
    ]);

    $response->assertSessionHasErrors(['support_email', 'timezone']);
});

test('activity is logged when general settings are updated', function () {
    $this->actingAs($this->admin)->put(route('admin.settings.general.update'), [
        'platform_name' => 'Test', 'support_email' => 'a@b.com', 'timezone' => 'UTC',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $this->admin->id,
        'description' => 'General settings updated',
    ]);
});
