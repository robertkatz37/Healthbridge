<?php

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Agency;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Str;

/**
 * Automatic invoice generation — every billable event (a subscription
 * charge, a Featured Listing purchase, a commission payout) produces
 * exactly one Invoice through here, so invoice numbering, tax, and
 * coupon application only need to be correct in one place.
 */
class InvoiceService
{
    public function __construct(
        private readonly PdfInvoiceService $pdf,
    ) {}

    /**
     * @param  array<array{description: string, amount: float, commission_id?: int}>  $lineItems
     */
    public function create(
        Agency $agency,
        InvoiceType $type,
        array $lineItems,
        ?Coupon $coupon = null,
        float $taxRate = 0.0,
        InvoiceStatus $status = InvoiceStatus::Pending,
    ): Invoice {
        $subtotal = round(array_sum(array_column($lineItems, 'amount')), 2);
        $discount = $coupon ? $coupon->discountFor($subtotal) : 0.0;
        $taxableAmount = max(0, $subtotal - $discount);
        $tax = round($taxableAmount * $taxRate, 2);
        $total = round($taxableAmount + $tax, 2);

        $invoice = Invoice::create([
            'agency_id' => $agency->id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'status' => $status->value,
            'invoice_type' => $type->value,
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'coupon_id' => $coupon?->id,
        ]);

        foreach ($lineItems as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'commission_id' => $item['commission_id'] ?? null,
                'description' => $item['description'],
                'amount' => $item['amount'],
            ]);
        }

        if ($coupon) {
            $coupon->increment('times_used');
        }

        activity()->performedOn($invoice)->log('Invoice ' . $invoice->invoice_number . ' generated (' . $type->label() . ')');

        $invoice->agency->user->notify(new \App\Notifications\Billing\InvoiceGenerated($invoice));

        return $invoice->fresh(['items']);
    }

    public function markPaid(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => InvoiceStatus::Paid->value, 'paid_at' => now()]);

        $this->pdf->generate($invoice->fresh(['items', 'agency']));

        return $invoice->fresh();
    }

    public function markFailed(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => InvoiceStatus::Failed->value]);

        return $invoice->fresh();
    }

    public function recordRefund(Invoice $invoice, float $amount): Invoice
    {
        $newRefundedTotal = min((float) $invoice->total_amount, (float) $invoice->refunded_amount + $amount);

        $invoice->update([
            'refunded_amount' => $newRefundedTotal,
            'status' => $newRefundedTotal >= (float) $invoice->total_amount ? InvoiceStatus::Refunded->value : $invoice->status->value,
        ]);

        return $invoice->fresh();
    }

    /**
     * Sequential, human-readable invoice numbers (INV-2026-000123) —
     * generated from the current count rather than the last row's
     * number, so a deleted/void invoice never causes a collision.
     */
    private function nextInvoiceNumber(): string
    {
        $year = now()->year;
        $count = Invoice::whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-%d-%06d', $year, $count);
    }
}
