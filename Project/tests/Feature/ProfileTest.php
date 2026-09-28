<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->withoutVite());

test('profile page is displayed for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/profile');

    $response->assertOk();
    $response->assertSee($user->name);
});

test('profile requires authentication', function () {
    $response = $this->get('/profile');

    $response->assertRedirect(route('login'));
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch('/profile/update', [
        'name'  => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertSessionHasNoErrors()
             ->assertRedirect();

    $user->refresh();

    $this->assertSame('Updated Name', $user->name);
    $this->assertSame('updated@example.com', $user->email);
});

test('email verification is cleared when email is changed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile/update', [
        'name'  => $user->name,
        'email' => 'newemail@example.com',
    ]);

    $this->assertNull($user->fresh()->email_verified_at);
});

test('email verification is preserved when email is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile/update', [
        'name'  => 'New Name',
        'email' => $user->email,
    ]);

    $this->assertNotNull($user->fresh()->email_verified_at);
});

test('avatar can be uploaded', function () {
    // GD extension is not available in this test environment; test the validation
    // pipeline with a file that passes MIME type checks using a known good path.
    // The actual file I/O (storage to disk) is tested via Storage::fake.
    Storage::fake('public');

    $user = User::factory()->create();

    // Write a valid minimal PNG to a temp file (pure bytes, no GD needed)
    $pngBytes = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
    );
    $tmpPath = sys_get_temp_dir() . '/test_avatar_' . uniqid() . '.png';
    file_put_contents($tmpPath, $pngBytes);

    $file = new \Illuminate\Http\UploadedFile($tmpPath, 'avatar.png', 'image/png', null, true);

    $response = $this->actingAs($user)->post('/profile/avatar', [
        'avatar' => $file,
    ]);

    @unlink($tmpPath);

    $response->assertRedirect();
    $this->assertNotNull($user->fresh()->avatar);
    Storage::disk('public')->assertExists($user->fresh()->avatar);
});

test('avatar upload rejects non-image files', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/profile/avatar', [
        'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors('avatar');
});

test('user can delete their account with correct password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete('/profile', [
        'password' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('account deletion requires correct password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete('/profile', [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertNotNull($user->fresh());
});

test('profile update is logged in activity log', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile/update', [
        'name'  => 'Updated Name',
        'email' => $user->email,
    ]);

    $this->assertDatabaseHas('activity_log', [
        'causer_id'   => $user->id,
        'description' => 'Profile updated',
    ]);
});

test('avatar url returns initials avatar when no avatar set', function () {
    $user = User::factory()->create(['avatar' => null]);

    expect($user->avatar_url)->toContain('ui-avatars.com');
});
