<?php

namespace App\Services\Billing;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;

/**
 * Records payment attempts against an Invoice and handles the
 * success/failure/retry outcomes coming back from Stripe (via webhook
 * — see StripeWebhookController). One Payment row per attempt, so a
 * failed-then-retried-and-succeeded invoice has a full attempt history
 * rather than a single row being overwritten.
 */
class PaymentService
{
    public function __construct(
        private readonly InvoiceService $invoices,
    ) {}

    public function recordSuccess(Invoice $invoice, float $amount, string $method, ?string $stripePaymentIntentId = null): Payment
    {
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'method' => $method,
            'status' => PaymentStatus::Succeeded->value,
            'stripe_payment_intent_id' => $stripePaymentIntentId,
            'paid_at' => now(),
        ]);

        $this->invoices->markPaid($invoice);

        activity()->performedOn($invoice)->log('Payment of $' . number_format($amount, 2) . ' received for invoice ' . $invoice->invoice_number);

        return $payment;
    }

    public function recordFailure(Invoice $invoice, float $amount, string $method, string $failureReason, ?string $stripePaymentIntentId = null): Payment
    {
        $existingAttempts = Payment::where('invoice_id', $invoice->id)->where('status', PaymentStatus::Failed->value)->count();

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'method' => $method,
            'status' => PaymentStatus::Failed->value,
            'failure_reason' => $failureReason,
            'retry_count' => $existingAttempts,
            'stripe_payment_intent_id' => $stripePaymentIntentId,
        ]);

        $this->invoices->markFailed($invoice);

        activity()->performedOn($invoice)->log('Payment failed for invoice ' . $invoice->invoice_number . ': ' . $failureReason);

        return $payment;
    }

    /**
     * Retry attempt count for an invoice's most recent failed payment
     * — used to decide whether Stripe's own dunning/retry schedule
     * (Smart Retries) has been exhausted, at which point the
     * subscription would move to past_due/unpaid via webhook.
     */
    public function retryCountFor(Invoice $invoice): int
    {
        return Payment::where('invoice_id', $invoice->id)->where('status', PaymentStatus::Failed->value)->count();
    }
}
