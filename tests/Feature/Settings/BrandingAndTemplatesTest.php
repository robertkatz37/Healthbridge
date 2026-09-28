<?php

use App\Models\EmailTemplate;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

// ─── Branding ───────────────────────────────────────────────────────────────

test('admin can update branding settings', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.settings.branding.update'), [
        'branding_company_name' => 'New Co',
        'branding_from_name' => 'New Co Support',
        'branding_from_email' => 'support@newco.com',
    ]);

    $response->assertRedirect();
    $settings = app(\App\Services\Settings\SettingsService::class);
    expect($settings->get('branding_company_name'))->toBe('New Co');
    expect($settings->get('branding_from_email'))->toBe('support@newco.com');
});

test('admin can upload a logo', function () {
    Storage::fake('public');
    $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $tmpPath = sys_get_temp_dir() . '/test_logo_' . uniqid() . '.png';
    file_put_contents($tmpPath, $pngBytes);
    $file = new \Illuminate\Http\UploadedFile($tmpPath, 'logo.png', 'image/png', null, true);

    $response = $this->actingAs($this->admin)->put(route('admin.settings.branding.update'), [
        'branding_company_name' => 'Test', 'branding_from_name' => 'Test', 'branding_from_email' => 'a@b.com',
        'logo' => $file,
    ]);

    @unlink($tmpPath);

    $response->assertRedirect();
    $path = app(\App\Services\Settings\SettingsService::class)->get('branding_logo_path');
    expect($path)->not->toBeEmpty();
    Storage::disk('public')->assertExists($path);
});

test('mail from address is derived from branding settings', function () {
    $settings = app(\App\Services\Settings\SettingsService::class);
    $settings->set('branding_from_email', 'custom@example.com', 'branding', 'string');
    $settings->set('branding_from_name', 'Custom Sender', 'branding', 'string');

    app(\App\Services\Settings\MailConfigService::class)->apply();

    expect(config('mail.from.address'))->toBe('custom@example.com');
    expect(config('mail.from.name'))->toBe('Custom Sender');
});

// ─── Email Templates ────────────────────────────────────────────────────────

test('admin can view the email templates list', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.settings.templates.index'));

    $response->assertOk();
    $response->assertSee('Agency Application Approved');
});

test('admin can edit an email template', function () {
    $template = EmailTemplate::where('key', 'agency_approved')->first();

    $response = $this->actingAs($this->admin)->put(route('admin.settings.templates.update', $template), [
        'subject' => 'Custom Subject {{agency_name}}',
        'body' => '<p>Custom body for {{agency_name}}</p>',
        'is_active' => '1',
    ]);

    $response->assertRedirect();
    $template->refresh();
    expect($template->subject)->toBe('Custom Subject {{agency_name}}');
    expect($template->is_active)->toBeTrue();
});

test('deactivating a template falls back to hardcoded content', function () {
    $template = EmailTemplate::where('key', 'agency_approved')->first();
    $this->actingAs($this->admin)->put(route('admin.settings.templates.update', $template), [
        'subject' => 'x', 'body' => 'y', 'is_active' => '0',
    ]);

    $rendered = app(\App\Services\Settings\EmailTemplateService::class)->render('agency_approved', ['agency_name' => 'Test']);

    expect($rendered)->toBeNull();
});

test('customized active template is actually used when rendering', function () {
    $template = EmailTemplate::where('key', 'agency_approved')->first();
    $this->actingAs($this->admin)->put(route('admin.settings.templates.update', $template), [
        'subject' => 'CUSTOM: {{agency_name}} is live',
        'body' => '<p>CUSTOM BODY {{agency_name}}</p>',
        'is_active' => '1',
    ]);

    $rendered = app(\App\Services\Settings\EmailTemplateService::class)->render('agency_approved', ['agency_name' => 'Sunrise']);

    expect($rendered['subject'])->toBe('CUSTOM: Sunrise is live');
    expect($rendered['body'])->toBe('<p>CUSTOM BODY Sunrise</p>');
});

test('template update requires a subject and body', function () {
    $template = EmailTemplate::where('key', 'agency_approved')->first();

    $response = $this->actingAs($this->admin)->put(route('admin.settings.templates.update', $template), []);

    $response->assertSessionHasErrors(['subject', 'body']);
});
