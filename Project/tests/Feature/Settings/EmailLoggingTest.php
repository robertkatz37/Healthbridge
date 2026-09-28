<?php

use App\Mail\TestEmail;
use App\Models\EmailLog;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

test('sending an email creates an email log entry via the MessageSent event', function () {
    // Real send (mail_mailer defaults to 'log' in seeded settings, so
    // this writes to storage/logs/laravel.log rather than attempting a
    // real network connection) — not Mail::fake(), specifically to prove
    // the MessageSent listener actually fires end-to-end.
    Mail::to('recipient@example.com')->send(new TestEmail('HealthsBridge'));

    expect(EmailLog::where('to_address', 'recipient@example.com')->where('status', 'sent')->exists())->toBeTrue();
});

test('admin can view the email logs screen', function () {
    EmailLog::factory()->create(['to_address' => 'someone@example.com', 'subject' => 'Test Subject', 'status' => 'sent']);

    $response = $this->actingAs($this->admin)->get(route('admin.settings.email-logs.index'));

    $response->assertOk();
    $response->assertSee('someone@example.com');
});

test('email logs can be filtered by status', function () {
    EmailLog::factory()->create(['to_address' => 'sent@example.com', 'status' => 'sent']);
    EmailLog::factory()->create(['to_address' => 'failed@example.com', 'status' => 'failed']);

    $response = $this->actingAs($this->admin)->get(route('admin.settings.email-logs.index', ['status' => 'failed']));

    $response->assertOk();
    $response->assertSee('failed@example.com');
    $response->assertDontSee('sent@example.com');
});

test('non-super-admin cannot view email logs', function () {
    $moderator = User::factory()->create()->assignRole('moderator');

    $response = $this->actingAs($moderator)->get(route('admin.settings.email-logs.index'));

    $response->assertStatus(403);
});

// ─── Failed Emails / Queue ──────────────────────────────────────────────────

test('admin can view the failed emails and queue screen', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.settings.failed-emails.index'));

    $response->assertOk();
});

test('admin can retry a failed job', function () {
    $uuid = (string) \Illuminate\Support\Str::uuid();
    \Illuminate\Support\Facades\DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\Notifications\Agency\ApplicationApproved']),
        'exception' => 'Connection refused',
        'failed_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.settings.failed-emails.retry', $uuid));

    $response->assertRedirect();
});

test('admin can delete a failed job', function () {
    $uuid = (string) \Illuminate\Support\Str::uuid();
    \Illuminate\Support\Facades\DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\Notifications\Agency\ApplicationApproved']),
        'exception' => 'Connection refused',
        'failed_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->delete(route('admin.settings.failed-emails.destroy', $uuid));

    $response->assertRedirect();
    expect(\Illuminate\Support\Facades\DB::table('failed_jobs')->where('uuid', $uuid)->exists())->toBeFalse();
});

// ─── System Page ────────────────────────────────────────────────────────────

test('admin can view the system diagnostics page', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.settings.system.show'));

    $response->assertOk();
    $response->assertSee('php version');
});
