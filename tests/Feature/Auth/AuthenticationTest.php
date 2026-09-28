<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('test@example.com|127.0.0.1');
    $this->withoutVite();
});

test('login screen renders with Bootstrap 5.3 design', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
    $response->assertSee('HealthsBridge');
    $response->assertSee('Sign In');
});

test('users can authenticate with valid credentials', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    // Redirects to dashboard (no 2FA since it's not set up on this user)
    $response->assertRedirect(route('dashboard'));
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users cannot authenticate with non-existent email', function () {
    $this->post('/login', [
        'email'    => 'nobody@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('remember me token is set when remember is checked', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email'    => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $this->assertAuthenticated();
    $this->assertNotNull($user->fresh()->remember_token);
});

test('login is rate limited after 5 failed attempts', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $errorMessage = session('errors')->first('email');
    // Should contain throttle message (seconds/minutes)
    $this->assertTrue(
        str_contains($errorMessage, 'seconds') || str_contains($errorMessage, 'Too many'),
        "Expected throttle error but got: {$errorMessage}"
    );
});

test('login history is recorded on successful login', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $this->assertDatabaseHas('login_histories', [
        'user_id' => $user->id,
        'status'  => 'success',
    ]);
});

test('last_login_at is updated on successful login', function () {
    $user = User::factory()->create();

    $this->assertNull($user->last_login_at);

    $this->post('/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $this->assertNotNull($user->fresh()->last_login_at);
});

test('authenticated users are redirected away from login page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/login');

    $response->assertRedirect(route('dashboard'));
});

test('guests cannot access the dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect(route('login'));
});
