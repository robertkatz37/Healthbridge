<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

test('admin can update mail settings', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.settings.mail.update'), [
        'mail_mailer' => 'smtp',
        'mail_host' => 'smtp.example.com',
        'mail_port' => 587,
        'mail_username' => 'testuser',
        'mail_password' => 'testpass123',
        'mail_encryption' => 'tls',
    ]);

    $response->assertRedirect();

    $settings = app(\App\Services\Settings\SettingsService::class);
    expect($settings->get('mail_mailer'))->toBe('smtp');
    expect($settings->get('mail_host'))->toBe('smtp.example.com');
    expect($settings->get('mail_password'))->toBe('testpass123');
});

test('mail password is encrypted at rest', function () {
    $this->actingAs($this->admin)->put(route('admin.settings.mail.update'), [
        'mail_mailer' => 'smtp', 'mail_host' => 'smtp.example.com', 'mail_port' => 587,
        'mail_password' => 'verysecretpassword', 'mail_encryption' => 'tls',
    ]);

    $raw = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'mail_password')->first();

    expect($raw->value)->not->toContain('verysecretpassword');
});

test('leaving password blank on update keeps the existing password', function () {
    $settings = app(\App\Services\Settings\SettingsService::class);
    $settings->set('mail_password', 'original_password', 'mail', 'string', encrypted: true);

    $this->actingAs($this->admin)->put(route('admin.settings.mail.update'), [
        'mail_mailer' => 'smtp', 'mail_host' => 'smtp.example.com', 'mail_port' => 587,
        'mail_password' => '', 'mail_encryption' => 'tls',
    ]);

    expect($settings->get('mail_password'))->toBe('original_password');
});

test('smtp host is required when mailer is smtp', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.settings.mail.update'), [
        'mail_mailer' => 'smtp', 'mail_encryption' => 'tls',
    ]);

    $response->assertSessionHasErrors(['mail_host', 'mail_port']);
});

test('smtp host is not required when mailer is log', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.settings.mail.update'), [
        'mail_mailer' => 'log', 'mail_encryption' => 'tls',
    ]);

    $response->assertSessionHasNoErrors();
});

test('admin can send a test email', function () {
    Mail::fake();

    $response = $this->actingAs($this->admin)->post(route('admin.settings.mail.test'), [
        'test_email' => 'recipient@example.com',
    ]);

    $response->assertRedirect();
    Mail::assertSent(\App\Mail\TestEmail::class);
});

test('test email requires a valid email address', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.settings.mail.test'), [
        'test_email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('test_email');
});

test('platform_admin cannot update mail settings', function () {
    $platformAdmin = User::factory()->create()->assignRole('platform_admin');

    $response = $this->actingAs($platformAdmin)->put(route('admin.settings.mail.update'), [
        'mail_mailer' => 'smtp', 'mail_host' => 'evil.com', 'mail_port' => 587, 'mail_encryption' => 'tls',
    ]);

    $response->assertStatus(403);
});
