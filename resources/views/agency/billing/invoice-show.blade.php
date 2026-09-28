<x-agency-layout title="Invoice {{ $invoice->invoice_number }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('agency.billing.index') }}" class="hb-link">Billing</a></li>
        <li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif
    @if($errors->any())
        <div class="hb-alert hb-alert-danger mb-4">{{ $errors->first() }}</div>
    @endif

    @php ['bg' => $bg, 'text' => $text] = $invoice->status->badgeColor(); @endphp

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h5 class="fw-bold mb-1" style="color:var(--hb-gray-900);">Invoice {{ $invoice->invoice_number }}</h5>
                    <span style="font-size:0.8rem;font-weight:600;background:{{ $bg }};color:{{ $text }};padding:0.2rem 0.6rem;border-radius:999px;">{{ $invoice->status->label() }}</span>
                </div>
                @if($invoice->pdf_path)
                    <a href="{{ route('agency.billing.invoices.download', $invoice) }}" class="btn btn-outline-primary btn-sm" style="border-radius:0.625rem;"><i class="bi bi-download me-1"></i>Download PDF</a>
                @endif
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;">Invoice Date</div>
                    <div style="font-weight:600;color:var(--hb-gray-900);">{{ $invoice->created_at->format('M d, Y') }}</div>
                </div>
                <div class="col-md-4">
                    <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;">Due Date</div>
                    <div style="font-weight:600;color:var(--hb-gray-900);">{{ $invoice->due_date->format('M d, Y') }}</div>
                </div>
                <div class="col-md-4">
                    <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;">Type</div>
                    <div style="font-weight:600;color:var(--hb-gray-900);">{{ $invoice->invoice_type->label() }}</div>
                </div>
            </div>

            <table class="table" style="font-size:0.9rem;">
                <thead style="background:var(--hb-gray-50);">
                    <tr><th class="px-3 py-2">Description</th><th class="px-3 py-2 text-end">Amount</th></tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr><td class="px-3 py-2">{{ $item->description }}</td><td class="px-3 py-2 text-end">${{ number_format((float) $item->amount, 2) }}</td></tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><td class="px-3 py-2 text-end">Subtotal</td><td class="px-3 py-2 text-end">${{ number_format((float) $invoice->subtotal_amount, 2) }}</td></tr>
                    @if((float) $invoice->tax_amount > 0)
                        <tr><td class="px-3 py-2 text-end">Tax</td><td class="px-3 py-2 text-end">${{ number_format((float) $invoice->tax_amount, 2) }}</td></tr>
                    @endif
                    @if((float) $invoice->refunded_amount > 0)
                        <tr><td class="px-3 py-2 text-end">Refunded</td><td class="px-3 py-2 text-end">-${{ number_format((float) $invoice->refunded_amount, 2) }}</td></tr>
                    @endif
                    <tr style="font-weight:700;"><td class="px-3 py-2 text-end">Total</td><td class="px-3 py-2 text-end">${{ number_format((float) $invoice->total_amount, 2) }}</td></tr>
                </tfoot>
            </table>

            @if($invoice->payments->isNotEmpty())
                <h6 class="fw-bold mt-4 mb-2" style="color:var(--hb-gray-900);">Payment Attempts</h6>
                @foreach($invoice->payments as $payment)
                    <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.85rem;">
                        <div>
                            <span class="fw-bold">${{ number_format((float) $payment->amount, 2) }}</span>
                            <span style="color:var(--hb-gray-600);"> via {{ ucfirst($payment->method) }} &middot; {{ $payment->created_at->format('M d, Y') }}</span>
                            @if($payment->status->value === 'failed')
                                <div style="color:#991B1B;font-size:0.8rem;">{{ $payment->failure_reason }}</div>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="hb-badge-verified">{{ $payment->status->label() }}</span>
                            @if($payment->status->value === 'succeeded' && (float) $payment->refunded_amount < (float) $payment->amount)
                                <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;" data-bs-toggle="modal" data-bs-target="#refundModal{{ $payment->id }}">Request Refund</button>
                            @endif
                        </div>
                    </div>

                    <div class="modal fade" id="refundModal{{ $payment->id }}" tabindex="-1">
                        <div class="modal-dialog"><div class="modal-content">
                            <form method="POST" action="{{ route('agency.billing.payments.refund-request', $payment) }}">
                                @csrf
                                <div class="modal-header"><h6 class="modal-title">Request a Refund</h6></div>
                                <div class="modal-body">
                                    <label class="hb-form-label">Amount</label>
                                    <input type="number" step="0.01" name="amount" class="hb-form-control mb-2" max="{{ (float) $payment->amount - (float) $payment->refunded_amount }}" value="{{ (float) $payment->amount - (float) $payment->refunded_amount }}" required>
                                    <label class="hb-form-label">Reason</label>
                                    <textarea name="reason" class="hb-form-control" rows="3" required></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Submit Request</button>
                                </div>
                            </form>
                        </div></div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-agency-layout>
