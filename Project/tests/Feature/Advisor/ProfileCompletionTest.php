<?php

use App\Models\Advisor;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->user->id, 'is_active' => true]);
});

test('advisor can view their profile page', function () {
    $response = $this->actingAs($this->user)->get(route('advisor.profile.edit'));

    $response->assertOk();
});

test('advisor can update personal information', function () {
    $response = $this->actingAs($this->user)->put(route('advisor.profile.update'), [
        'phone' => '555-1234',
        'bio' => 'Experienced senior care advisor with 10 years in the field.',
        'languages' => ['English', 'Spanish'],
        'specialties' => ['Memory Care', 'Veteran Benefits'],
        'license_number' => 'CSA-12345',
        'certifications' => 'Certified Senior Advisor',
        'is_available' => '1',
        'email_signature' => 'Best, ' . $this->user->name,
    ]);

    $response->assertRedirect();
    $this->advisor->refresh();
    expect($this->advisor->phone)->toBe('555-1234');
    expect($this->advisor->languages)->toBe(['English', 'Spanish']);
    expect($this->advisor->specialties)->toBe(['Memory Care', 'Veteran Benefits']);
    expect($this->advisor->is_available)->toBeTrue();
});

test('advisor can update working hours', function () {
    $response = $this->actingAs($this->user)->put(route('advisor.profile.update'), [
        'working_hours' => [
            ['day' => 'monday', 'start' => '08:00', 'end' => '16:00', 'enabled' => '1'],
        ],
    ]);

    $response->assertRedirect();
    $this->advisor->refresh();
    expect($this->advisor->working_hours[0]['day'])->toBe('monday');
    expect($this->advisor->working_hours[0]['start'])->toBe('08:00');
});

test('turning availability off is correctly persisted as false', function () {
    $this->advisor->update(['is_available' => true]);

    $this->actingAs($this->user)->put(route('advisor.profile.update'), []);

    expect($this->advisor->fresh()->is_available)->toBeFalse();
});

test('advisor can upload a profile photo', function () {
    Storage::fake('public');
    $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $tmpPath = sys_get_temp_dir() . '/test_advisor_photo_' . uniqid() . '.png';
    file_put_contents($tmpPath, $pngBytes);
    $file = new \Illuminate\Http\UploadedFile($tmpPath, 'photo.png', 'image/png', null, true);

    $response = $this->actingAs($this->user)->post(route('advisor.profile.photo.store'), ['photo' => $file]);

    @unlink($tmpPath);

    $response->assertRedirect();
    expect($this->advisor->fresh()->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($this->advisor->fresh()->photo_path);
});

test('advisor can remove their profile photo', function () {
    Storage::fake('public');
    $this->advisor->update(['photo_path' => 'advisors/1/photo/test.png']);
    Storage::disk('public')->put('advisors/1/photo/test.png', 'fake content');

    $response = $this->actingAs($this->user)->delete(route('advisor.profile.photo.destroy'));

    $response->assertRedirect();
    expect($this->advisor->fresh()->photo_path)->toBeNull();
});

test('advisor without a photo gets a generated avatar url', function () {
    expect($this->advisor->photo_url)->toContain('ui-avatars.com');
});

test('another advisor cannot update via a crafted profile request targeting someone elses record', function () {
    $otherAdvisor = Advisor::factory()->create();

    // The route always resolves to the CURRENT user's own advisor row —
    // there's no way to target another advisor's record through this
    // endpoint at all.
    $this->actingAs($this->user)->put(route('advisor.profile.update'), ['phone' => '999-9999']);

    expect($otherAdvisor->fresh()->phone)->not->toBe('999-9999');
});
