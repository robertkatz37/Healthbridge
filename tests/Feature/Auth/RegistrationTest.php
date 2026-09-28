<?php

use App\Models\User;

beforeEach(fn () => $this->withoutVite());

test('registration screen renders with role selector', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    $response->assertSee('Create your account');
    $response->assertSee('family');
    $response->assertSee('agency_owner');
});

test('family users can register successfully', function () {
    $response = $this->post('/register', [
        'name'                  => 'Jane Smith',
        'email'                 => 'jane@example.com',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
        'role'                  => 'family',
        'terms'                 => '1',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'jane@example.com')->firstOrFail();
    expect($user->hasRole('family'))->toBeTrue();
    expect($user->family)->not->toBeNull();
});

test('agency owner users can register successfully', function () {
    $response = $this->post('/register', [
        'name'                  => 'Agency Owner',
        'email'                 => 'owner@example.com',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
        'role'                  => 'agency_owner',
        'terms'                 => '1',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'owner@example.com')->firstOrFail();
    expect($user->hasRole('agency_owner'))->toBeTrue();
});

test('registration fails without accepting terms', function () {
    $response = $this->post('/register', [
        'name'                  => 'Jane Smith',
        'email'                 => 'jane@example.com',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
        'role'                  => 'family',
        // terms omitted
    ]);

    $response->assertSessionHasErrors('terms');
    $this->assertGuest();
});

test('registration fails with invalid role', function () {
    $response = $this->post('/register', [
        'name'                  => 'Hacker',
        'email'                 => 'hacker@example.com',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
        'role'                  => 'super_admin',
        'terms'                 => '1',
    ]);

    $response->assertSessionHasErrors('role');
    $this->assertGuest();
});

test('registration fails with weak password', function () {
    $response = $this->post('/register', [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password', // no uppercase or numbers
        'password_confirmation' => 'password',
        'role'                  => 'family',
        'terms'                 => '1',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('registration fails with duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post('/register', [
        'name'                  => 'Second User',
        'email'                 => 'taken@example.com',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
        'role'                  => 'family',
        'terms'                 => '1',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('new registered user is redirected to email verification', function () {
    $response = $this->post('/register', [
        'name'                  => 'New User',
        'email'                 => 'newuser@example.com',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
        'role'                  => 'family',
        'terms'                 => '1',
    ]);

    $response->assertRedirect(route('verification.notice'));
});

test('registration is logged in activity log', function () {
    $this->post('/register', [
        'name'                  => 'Logged User',
        'email'                 => 'logged@example.com',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
        'role'                  => 'family',
        'terms'                 => '1',
    ]);

    $user = User::where('email', 'logged@example.com')->firstOrFail();

    $this->assertDatabaseHas('activity_log', [
        'causer_id'   => $user->id,
        'description' => 'User registered',
    ]);
});
