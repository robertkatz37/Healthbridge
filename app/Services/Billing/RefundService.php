<?php

namespace App\Services\Billing;

use App\Enums\RefundStatus;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;

/**
 * Refund requests and admin approval — a refund always starts as a
 * request (from an agency owner or raised internally by an admin) and
 * only actually moves money once approved, matching the phase's
 * explicit "Refund requests -> Admin approval" flow rather than
 * letting anyone refund a payment directly.
 */
class RefundService
{
    public function __construct(
        private readonly StripeGateway $stripe,
        private readonly InvoiceService $invoices,
    ) {}

    public function request(Payment $payment, float $amount, string $reason, User $requestedBy): Refund
    {
        abort_if($amount > (float) $payment->amount - (float) $payment->refunded_amount, 422, 'Refund amount exceeds the remaining refundable balance.');

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => RefundStatus::Requested->value,
            'requested_by' => $requestedBy->id,
        ]);

        activity()->causedBy($requestedBy)->performedOn($refund)->log('Refund requested for payment #' . $payment->id . ' ($' . number_format($amount, 2) . ')');

        return $refund;
    }

    public function approve(Refund $refund, User $approvedBy, ?string $adminNotes = null): Refund
    {
        abort_unless($refund->status === RefundStatus::Requested, 422, 'Only a requested refund can be approved.');

        $payment = $refund->payment;

        if ($payment->stripe_payment_intent_id && !str_starts_with($payment->stripe_payment_intent_id, 'local_')) {
            $stripeRefund = $this->stripe->createRefund($payment->stripe_payment_intent_id, (int) round($refund->amount * 100));
            $refund->stripe_refund_id = $stripeRefund['id'] ?? null;
        }

        $refund->update([
            'status' => RefundStatus::Processed->value,
            'approved_by' => $approvedBy->id,
            'admin_notes' => $adminNotes,
            'processed_at' => now(),
            'stripe_refund_id' => $refund->stripe_refund_id,
        ]);

        $payment->increment('refunded_amount', $refund->amount);
        $payment->update(['status' => $payment->isFullyRefunded() ? 'refunded' : $payment->status->value]);

        $this->invoices->recordRefund($payment->invoice, $refund->amount);

        activity()->causedBy($approvedBy)->performedOn($refund)->log('Refund approved and processed ($' . number_format($refund->amount, 2) . ')');

        $payment->invoice->agency->user->notify(new \App\Notifications\Billing\RefundProcessed($refund->fresh(['invoice'])));

        return $refund->fresh();
    }

    public function reject(Refund $refund, User $rejectedBy, string $adminNotes): Refund
    {
        abort_unless($refund->status === RefundStatus::Requested, 422, 'Only a requested refund can be rejected.');

        $refund->update([
            'status' => RefundStatus::Rejected->value,
            'approved_by' => $rejectedBy->id,
            'admin_notes' => $adminNotes,
        ]);

        activity()->causedBy($rejectedBy)->performedOn($refund)->log('Refund request rejected');

        return $refund->fresh();
    }
}
