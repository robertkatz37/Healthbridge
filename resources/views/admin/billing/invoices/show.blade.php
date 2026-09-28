<x-admin-layout title="Invoice {{ $invoice->invoice_number }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.billing.invoices.index') }}" class="hb-link">Invoice Management</a></li>
        <li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li>
    @endslot

    @php ['bg' => $bg, 'text' => $text] = $invoice->status->badgeColor(); @endphp

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h5 class="fw-bold mb-1" style="color:var(--hb-gray-900);">Invoice {{ $invoice->invoice_number }}</h5>
                    <span style="font-size:0.8rem;font-weight:600;background:{{ $bg }};color:{{ $text }};padding:0.2rem 0.6rem;border-radius:999px;">{{ $invoice->status->label() }}</span>
                </div>
                <a href="{{ route('admin.agencies.show', $invoice->agency) }}" class="hb-link">{{ $invoice->agency->name ?? 'N/A' }}</a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;">Type</div>
                    <div style="font-weight:600;color:var(--hb-gray-900);">{{ $invoice->invoice_type->label() }}</div>
                </div>
                <div class="col-md-3">
                    <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;">Invoice Date</div>
                    <div style="font-weight:600;color:var(--hb-gray-900);">{{ $invoice->created_at->format('M d, Y') }}</div>
                </div>
                <div class="col-md-3">
                    <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;">Due Date</div>
                    <div style="font-weight:600;color:var(--hb-gray-900);">{{ $invoice->due_date->format('M d, Y') }}</div>
                </div>
                <div class="col-md-3">
                    <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;">Paid At</div>
                    <div style="font-weight:600;color:var(--hb-gray-900);">{{ $invoice->paid_at?->format('M d, Y') ?? '—' }}</div>
                </div>
            </div>

            <table class="table" style="font-size:0.9rem;">
                <thead style="background:var(--hb-gray-50);"><tr><th class="px-3 py-2">Description</th><th class="px-3 py-2 text-end">Amount</th></tr></thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr><td class="px-3 py-2">{{ $item->description }}</td><td class="px-3 py-2 text-end">${{ number_format((float) $item->amount, 2) }}</td></tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><td class="px-3 py-2 text-end">Subtotal</td><td class="px-3 py-2 text-end">${{ number_format((float) $invoice->subtotal_amount, 2) }}</td></tr>
                    <tr><td class="px-3 py-2 text-end">Tax</td><td class="px-3 py-2 text-end">${{ number_format((float) $invoice->tax_amount, 2) }}</td></tr>
                    <tr><td class="px-3 py-2 text-end">Refunded</td><td class="px-3 py-2 text-end">-${{ number_format((float) $invoice->refunded_amount, 2) }}</td></tr>
                    <tr style="font-weight:700;"><td class="px-3 py-2 text-end">Total</td><td class="px-3 py-2 text-end">${{ number_format((float) $invoice->total_amount, 2) }}</td></tr>
                </tfoot>
            </table>

            @if($invoice->payments->isNotEmpty())
                <h6 class="fw-bold mt-4 mb-2" style="color:var(--hb-gray-900);">Payments</h6>
                @foreach($invoice->payments as $payment)
                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.85rem;">
                        <div>
                            <span class="fw-bold">${{ number_format((float) $payment->amount, 2) }}</span>
                            <span style="color:var(--hb-gray-600);"> via {{ ucfirst($payment->method) }} &middot; {{ $payment->created_at->format('M d, Y') }}</span>
                            @if($payment->status->value === 'failed')
                                <div style="color:#991B1B;">{{ $payment->failure_reason }}</div>
                            @endif
                        </div>
                        <span class="hb-badge-verified">{{ $payment->status->label() }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-admin-layout>
