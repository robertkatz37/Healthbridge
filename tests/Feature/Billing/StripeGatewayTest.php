<?php

use App\Services\Billing\StripeGateway;
use App\Services\Billing\StripeGatewayException;

test('a correctly signed webhook payload verifies successfully', function () {
    $secret = 'whsec_test_secret';
    $payload = json_encode(['type' => 'invoice.paid', 'id' => 'evt_123']);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    $header = "t={$timestamp},v1={$signature}";

    $gateway = new StripeGateway();

    expect($gateway->verifyWebhookSignature($payload, $header, $secret))->toBeTrue();
});

test('a tampered payload fails signature verification', function () {
    $secret = 'whsec_test_secret';
    $payload = json_encode(['type' => 'invoice.paid', 'id' => 'evt_123']);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    $header = "t={$timestamp},v1={$signature}";

    $gateway = new StripeGateway();
    $tamperedPayload = json_encode(['type' => 'invoice.paid', 'id' => 'evt_999']);

    expect($gateway->verifyWebhookSignature($tamperedPayload, $header, $secret))->toBeFalse();
});

test('a webhook signed with the wrong secret fails verification', function () {
    $payload = json_encode(['type' => 'invoice.paid']);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, 'whsec_wrong_secret');
    $header = "t={$timestamp},v1={$signature}";

    $gateway = new StripeGateway();

    expect($gateway->verifyWebhookSignature($payload, $header, 'whsec_test_secret'))->toBeFalse();
});

test('an old timestamp outside the tolerance window is rejected to prevent replay', function () {
    $secret = 'whsec_test_secret';
    $payload = json_encode(['type' => 'invoice.paid']);
    $oldTimestamp = time() - 3600;
    $signature = hash_hmac('sha256', $oldTimestamp . '.' . $payload, $secret);
    $header = "t={$oldTimestamp},v1={$signature}";

    $gateway = new StripeGateway();

    expect($gateway->verifyWebhookSignature($payload, $header, $secret))->toBeFalse();
});

test('a malformed signature header is rejected rather than erroring', function () {
    $gateway = new StripeGateway();

    expect($gateway->verifyWebhookSignature('{}', 'not-a-valid-header', 'whsec_test'))->toBeFalse();
    expect($gateway->verifyWebhookSignature('{}', '', 'whsec_test'))->toBeFalse();
});

test('createCustomer sends the correct request and returns the decoded response', function () {
    Http::fake([
        'api.stripe.com/v1/customers' => Http::response(['id' => 'cus_test123', 'email' => 'owner@example.com'], 200),
    ]);

    $gateway = new StripeGateway('sk_test_fake');
    $result = $gateway->createCustomer('owner@example.com', 'Test Owner', ['agency_id' => '5']);

    expect($result['id'])->toBe('cus_test123');
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.stripe.com/v1/customers'
            && $request['email'] === 'owner@example.com'
            && $request->hasHeader('Authorization', 'Bearer sk_test_fake');
    });
});

test('a failed Stripe API response throws StripeGatewayException with the Stripe error message', function () {
    Http::fake([
        'api.stripe.com/v1/customers' => Http::response(['error' => ['message' => 'Invalid API key', 'type' => 'authentication_error']], 401),
    ]);

    $gateway = new StripeGateway('sk_invalid');

    expect(fn () => $gateway->createCustomer('a@b.com', 'Test'))
        ->toThrow(StripeGatewayException::class, 'Invalid API key');
});
