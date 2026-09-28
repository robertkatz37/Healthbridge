<x-agency-layout title="Certifications">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Certifications</li>
    @endslot

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Certifications & Licenses</h6>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCertModal" style="border-radius:0.625rem;">
            <i class="bi bi-plus-lg me-1"></i> Add Certification
        </button>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($certifications->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-patch-check" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No certifications added yet.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Certification</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Issuing Body</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Expires</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($certifications as $cert)
                                @php
                                    $isExpiringSoon = $cert->expires_at && $cert->expires_at->diffInDays(now(), false) > -30 && $cert->expires_at->isFuture();
                                    $isExpired = $cert->expires_at && $cert->expires_at->isPast();
                                @endphp
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3">
                                        <div class="fw-bold" style="color:var(--hb-gray-900);">
                                            <i class="bi bi-patch-check-fill me-1" style="color:var(--hb-emerald-700);"></i>
                                            {{ $cert->name }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ $cert->issuing_body ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if($cert->expires_at)
                                            <span style="color:{{ $isExpired ? 'var(--hb-danger)' : ($isExpiringSoon ? 'var(--hb-warning)' : 'var(--hb-gray-900)') }};">
                                                {{ $cert->expires_at->format('M d, Y') }}
                                                @if($isExpired) <i class="bi bi-exclamation-circle ms-1" title="Expired"></i>
                                                @elseif($isExpiringSoon) <i class="bi bi-clock ms-1" title="Expiring soon"></i>
                                                @endif
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <form method="POST" action="{{ route('agency.certifications.destroy', $cert) }}" onsubmit="return confirm('Remove this certification?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="addCertModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold">Add Certification</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('agency.certifications.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="hb-form-label">Certification name</label>
                            <input type="text" name="name" class="hb-form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="hb-form-label">Issuing body</label>
                            <input type="text" name="issuing_body" class="hb-form-control">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="hb-form-label">Issued date</label>
                                <input type="date" name="issued_at" class="hb-form-control">
                            </div>
                            <div class="col-6">
                                <label class="hb-form-label">Expiry date</label>
                                <input type="date" name="expires_at" class="hb-form-control">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="hb-form-label">Document (optional)</label>
                            <input type="file" name="document" class="hb-form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Add Certification</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-agency-layout>
