<x-admin-layout title="Refund Requests">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Refund Requests</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif
    @if($errors->any())
        <div class="hb-alert hb-alert-danger mb-4">{{ $errors->first() }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-4">
        @foreach(['' => 'All', 'requested' => 'Pending', 'processed' => 'Processed', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('admin.billing.refunds.index', ['status' => $key]) }}"
               class="btn btn-sm {{ request('status', '') === $key ? 'btn-primary' : 'btn-outline-secondary' }}" style="border-radius:999px;">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($refundRequests->isEmpty())
                <div class="text-center py-5"><p style="color:var(--hb-gray-600);margin:0;">No refund requests found.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3">Agency</th>
                                <th class="px-4 py-3">Invoice</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Reason</th>
                                <th class="px-4 py-3">Requested By</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($refundRequests as $refund)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3 fw-bold">{{ $refund->payment->invoice->agency->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $refund->payment->invoice->invoice_number ?? '' }}</td>
                                    <td class="px-4 py-3">${{ number_format((float) $refund->amount, 2) }}</td>
                                    <td class="px-4 py-3" style="max-width:220px;">{{ Str::limit($refund->reason, 60) }}</td>
                                    <td class="px-4 py-3">{{ $refund->requester?->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $badgeColors = ['requested' => ['#FFFBEB', '#92400E'], 'processed' => ['var(--hb-emerald-100)', 'var(--hb-emerald-700)'], 'rejected' => ['#FEF2F2', '#991B1B']];
                                            [$bg, $text] = $badgeColors[$refund->status->value] ?? ['#F3F4F6', '#374151'];
                                        @endphp
                                        <span style="font-size:0.75rem;font-weight:600;background:{{ $bg }};color:{{ $text }};padding:0.2rem 0.6rem;border-radius:999px;">{{ $refund->status->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        @if($refund->status->value === 'requested')
                                            <button type="button" class="btn btn-sm btn-primary" style="border-radius:0.5rem;" data-bs-toggle="modal" data-bs-target="#approveModal{{ $refund->id }}">Approve</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $refund->id }}">Reject</button>
                                        @else
                                            <span style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $refund->processed_at?->format('M d, Y') }}</span>
                                        @endif
                                    </td>
                                </tr>

                                <div class="modal fade" id="approveModal{{ $refund->id }}" tabindex="-1">
                                    <div class="modal-dialog"><div class="modal-content">
                                        <form method="POST" action="{{ route('admin.billing.refunds.approve', $refund) }}">
                                            @csrf
                                            <div class="modal-header"><h6 class="modal-title">Approve Refund — ${{ number_format((float) $refund->amount, 2) }}</h6></div>
                                            <div class="modal-body">
                                                <label class="hb-form-label">Admin Notes (optional)</label>
                                                <textarea name="admin_notes" class="hb-form-control" rows="3"></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Approve & Process</button>
                                            </div>
                                        </form>
                                    </div></div>
                                </div>

                                <div class="modal fade" id="rejectModal{{ $refund->id }}" tabindex="-1">
                                    <div class="modal-dialog"><div class="modal-content">
                                        <form method="POST" action="{{ route('admin.billing.refunds.reject', $refund) }}">
                                            @csrf
                                            <div class="modal-header"><h6 class="modal-title">Reject Refund Request</h6></div>
                                            <div class="modal-body">
                                                <label class="hb-form-label">Reason (required)</label>
                                                <textarea name="admin_notes" class="hb-form-control" rows="3" required></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Reject</button>
                                            </div>
                                        </form>
                                    </div></div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $refundRequests->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
