<x-admin-layout title="Invoice Management">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Invoice Management</li>
    @endslot

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-3">
            <input type="text" name="agency" class="hb-form-control" placeholder="Search by agency..." value="{{ request('agency') }}">
        </div>
        <div class="col-md-3">
            <select name="type" class="hb-form-control">
                <option value="">All Types</option>
                <option value="subscription" {{ request('type') === 'subscription' ? 'selected' : '' }}>Subscription</option>
                <option value="featured_listing" {{ request('type') === 'featured_listing' ? 'selected' : '' }}>Featured Listing</option>
                <option value="addon" {{ request('type') === 'addon' ? 'selected' : '' }}>Add-on</option>
                <option value="commission" {{ request('type') === 'commission' ? 'selected' : '' }}>Commission</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="hb-form-control">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
        </div>
    </form>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($invoices->isEmpty())
                <div class="text-center py-5"><p style="color:var(--hb-gray-600);margin:0;">No invoices match these filters.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3">Invoice</th>
                                <th class="px-4 py-3">Agency</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                @php ['bg' => $bg, 'text' => $text] = $invoice->status->badgeColor(); @endphp
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3">{{ $invoice->invoice_number }}</td>
                                    <td class="px-4 py-3">{{ $invoice->agency->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $invoice->invoice_type->label() }}</td>
                                    <td class="px-4 py-3">${{ number_format((float) $invoice->total_amount, 2) }}</td>
                                    <td class="px-4 py-3"><span style="font-size:0.75rem;font-weight:600;background:{{ $bg }};color:{{ $text }};padding:0.2rem 0.6rem;border-radius:999px;">{{ $invoice->status->label() }}</span></td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $invoice->created_at->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $invoices->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
