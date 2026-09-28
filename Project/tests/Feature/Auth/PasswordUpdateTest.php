<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => $this->withoutVite());

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/profile/password', [
        'current_password'      => 'password',
        'password'              => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertSessionHasNoErrors()
             ->assertRedirect();

    $this->assertTrue(Hash::check('NewPassword1!', $user->fresh()->password));
});

test('correct current password must be provided to change password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/profile/password', [
        'current_password'      => 'wrong-password',
        'password'              => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $response->assertSessionHasErrors('current_password');
});

test('weak password is rejected on update', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/profile/password', [
        'current_password'      => 'password',
        'password'              => 'simple',
        'password_confirmation' => 'simple',
    ]);

    $response->assertSessionHasErrors('password');
});

test('password change is logged in activity log', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/profile/password', [
        'current_password'      => 'password',
        'password'              => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'causer_id'   => $user->id,
        'description' => 'Password changed',
    ]);
});
