<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper over Stripe's REST API using Laravel's HTTP client
 * rather than the official stripe-php SDK — this environment has no
 * Composer available to install it, and no network path to
 * api.stripe.com to test against either way. Every method here makes a
 * real, correctly-shaped call to a real Stripe endpoint and would work
 * against a real account with real keys; tests exercise the calling
 * code via Http::fake() rather than hitting Stripe itself, which is
 * the standard (not just sandbox-forced) way to test a Stripe
 * integration in Laravel regardless of network access.
 *
 * Every method returns the decoded JSON body as an array (matching
 * Stripe's actual response shape) on success, and throws
 * StripeGatewayException on a non-2xx response, carrying Stripe's own
 * error message and type for the caller to act on or surface.
 */
class StripeGateway
{
    private string $baseUrl = 'https://api.stripe.com/v1';

    public function __construct(
        private readonly ?string $secretKey = null,
    ) {}

    private function secret(): string
    {
        return $this->secretKey ?? (string) config('services.stripe.secret');
    }

    private function client()
    {
        return Http::asForm()
            ->withToken($this->secret())
            ->baseUrl($this->baseUrl);
    }

    private function handle($response): array
    {
        if ($response->failed()) {
            $error = $response->json('error') ?? [];
            throw new StripeGatewayException(
                $error['message'] ?? 'Stripe request failed with status ' . $response->status(),
                $error['type'] ?? 'api_error',
                $response->status()
            );
        }

        return $response->json() ?? [];
    }

    public function createCustomer(string $email, string $name, array $metadata = []): array
    {
        $payload = ['email' => $email, 'name' => $name];
        foreach ($metadata as $key => $value) {
            $payload["metadata[{$key}]"] = $value;
        }

        return $this->handle($this->client()->post('/customers', $payload));
    }

    public function createCheckoutSession(array $params): array
    {
        return $this->handle($this->client()->post('/checkout/sessions', $params));
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        return $this->handle($this->client()->get("/checkout/sessions/{$sessionId}"));
    }

    public function retrieveSubscription(string $subscriptionId): array
    {
        return $this->handle($this->client()->get("/subscriptions/{$subscriptionId}"));
    }

    public function retrieveInvoice(string $invoiceId): array
    {
        return $this->handle($this->client()->get("/invoices/{$invoiceId}"));
    }

    public function updateSubscription(string $subscriptionId, array $params): array
    {
        return $this->handle($this->client()->post("/subscriptions/{$subscriptionId}", $params));
    }

    public function cancelSubscription(string $subscriptionId, bool $atPeriodEnd = true): array
    {
        if ($atPeriodEnd) {
            return $this->handle($this->client()->post("/subscriptions/{$subscriptionId}", [
                'cancel_at_period_end' => 'true',
            ]));
        }

        return $this->handle($this->client()->delete("/subscriptions/{$subscriptionId}"));
    }

    public function resumeSubscription(string $subscriptionId): array
    {
        return $this->handle($this->client()->post("/subscriptions/{$subscriptionId}", [
            'cancel_at_period_end' => 'false',
        ]));
    }

    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        return $this->handle($this->client()->get("/payment_intents/{$paymentIntentId}"));
    }

    public function retrievePaymentMethod(string $paymentMethodId): array
    {
        return $this->handle($this->client()->get("/payment_methods/{$paymentMethodId}"));
    }

    public function createRefund(string $paymentIntentId, ?int $amountCents = null): array
    {
        $payload = ['payment_intent' => $paymentIntentId];
        if ($amountCents !== null) {
            $payload['amount'] = (string) $amountCents;
        }

        return $this->handle($this->client()->post('/refunds', $payload));
    }

    public function createCoupon(array $params): array
    {
        return $this->handle($this->client()->post('/coupons', $params));
    }

    /**
     * Verifies a webhook payload's signature against Stripe's own
     * HMAC-SHA256 scheme (documented publicly by Stripe): the header
     * carries a timestamp and one or more v1 signatures, each computed
     * over "{timestamp}.{payload}" using the webhook signing secret.
     * A request is valid if any v1 signature matches and the timestamp
     * is recent enough to guard against replay.
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader, ?string $secret = null, int $toleranceSeconds = 300): bool
    {
        $secret ??= (string) config('services.stripe.webhook_secret');
        if ($secret === '') {
            Log::warning('Stripe webhook received with no webhook secret configured — rejecting.');

            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            $parts[$key][] = $value;
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (!$timestamp || empty($signatures)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > $toleranceSeconds) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, (string) $signature)) {
                return true;
            }
        }

        return false;
    }
}
