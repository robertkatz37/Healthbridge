<?php

use App\Models\User;
use App\Services\Auth\TotpService;

beforeEach(fn () => $this->withoutVite());

test('2FA setup page requires authentication', function () {
    $response = $this->get('/two-factor/setup');

    $response->assertRedirect(route('login'));
});

test('2FA setup page renders for authenticated verified users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/two-factor/setup');

    $response->assertStatus(200);
    $response->assertSee('Two-Factor Authentication');
});

test('2FA secret is generated when visiting setup page', function () {
    $user = User::factory()->create();

    $this->assertNull($user->two_factor_secret);

    $this->actingAs($user)->get('/two-factor/setup');

    $this->assertNotNull($user->fresh()->two_factor_secret);
});

test('2FA can be enabled with a valid TOTP code', function () {
    $user = User::factory()->create();
    $totp = app(TotpService::class);

    // Simulate visiting setup to generate the secret
    $this->actingAs($user)->get('/two-factor/setup');
    $user->refresh();

    $validCode = $totp->currentCode($user->two_factor_secret);

    $response = $this->actingAs($user)->post('/two-factor/enable', [
        'code' => $validCode,
    ]);

    $response->assertRedirect(route('two-factor.recovery-codes'));

    $user->refresh();
    $this->assertNotNull($user->two_factor_confirmed_at);
    $this->assertNotNull($user->two_factor_recovery_codes);
});

test('2FA cannot be enabled with an invalid code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/two-factor/setup');
    $user->refresh();

    $response = $this->actingAs($user)->post('/two-factor/enable', [
        'code' => '000000',
    ]);

    $response->assertSessionHasErrors('code');
    $this->assertNull($user->fresh()->two_factor_confirmed_at);
});

test('2FA can be disabled with correct password', function () {
    $user = User::factory()->create();
    $totp = app(TotpService::class);

    // Enable 2FA first
    $this->actingAs($user)->get('/two-factor/setup');
    $user->refresh();
    $this->actingAs($user)->post('/two-factor/enable', [
        'code' => $totp->currentCode($user->two_factor_secret),
    ]);
    $user->refresh();
    $this->assertTrue($user->hasTwoFactorEnabled());

    // Now disable
    $response = $this->actingAs($user)->post('/two-factor/disable', [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('profile.edit'));

    $user->refresh();
    $this->assertNull($user->two_factor_secret);
    $this->assertNull($user->two_factor_confirmed_at);
});

test('2FA cannot be disabled with wrong password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/two-factor/disable', [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');
});

test('recovery codes are generated as an 8-element array', function () {
    $totp  = app(TotpService::class);
    $codes = $totp->generateRecoveryCodes();

    expect($codes)->toBeArray()->toHaveCount(8);
    foreach ($codes as $code) {
        expect($code)->toMatch('/^[A-Z0-9]{5}-[A-Z0-9]{5}$/');
    }
});

test('TOTP service verifies current code correctly', function () {
    $totp   = app(TotpService::class);
    $secret = $totp->generateSecret();
    $code   = $totp->currentCode($secret);

    expect($totp->verify($secret, $code))->toBeTrue();
});

test('TOTP service rejects wrong code', function () {
    $totp   = app(TotpService::class);
    $secret = $totp->generateSecret();

    expect($totp->verify($secret, '000000'))->toBeFalse();
});

test('recovery code can be verified and consumed', function () {
    $totp    = app(TotpService::class);
    $codes   = $totp->generateRecoveryCodes();
    $hashed  = $totp->hashRecoveryCodes($codes);
    $plain   = $codes[0];

    $remaining = $totp->verifyAndConsumeRecoveryCode($plain, $hashed);

    expect($remaining)->toBeArray()->toHaveCount(7);
});

test('invalid recovery code is rejected', function () {
    $totp   = app(TotpService::class);
    $codes  = $totp->generateRecoveryCodes();
    $hashed = $totp->hashRecoveryCodes($codes);

    $result = $totp->verifyAndConsumeRecoveryCode('AAAAA-BBBBB', $hashed);

    expect($result)->toBeFalse();
});
