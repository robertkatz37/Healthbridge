<x-agency-layout title="Documents">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Documents</li>
    @endslot

    <div class="hb-alert hb-alert-info mb-3">
        <i class="bi bi-shield-lock me-2"></i>
        These documents are private and only visible to your team and platform administrators.
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Insurance & License Documents</h6>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDocModal" style="border-radius:0.625rem;">
            <i class="bi bi-upload me-1"></i> Upload Document
        </button>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($documents->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-lock" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No documents uploaded yet.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Document</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Type</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Expires</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documents as $doc)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3">
                                        <i class="bi bi-file-earmark-text me-1" style="color:var(--hb-gray-600);"></i>
                                        {{ $doc->original_filename }}
                                    </td>
                                    <td class="px-4 py-3">{{ ucfirst($doc->document_type->value) }}</td>
                                    <td class="px-4 py-3">
                                        @if($doc->verified_at)
                                            <span class="hb-badge-verified"><i class="bi bi-check-circle me-1"></i>Verified</span>
                                        @else
                                            <span style="font-size:0.7rem;font-weight:600;background:var(--hb-gray-200);color:var(--hb-gray-600);padding:0.2rem 0.6rem;border-radius:999px;">Pending Verification</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $doc->expires_at?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('agency.documents.download', $doc) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <form method="POST" action="{{ route('agency.documents.destroy', $doc) }}" class="d-inline" onsubmit="return confirm('Remove this document?')">
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

    <div class="modal fade" id="addDocModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold">Upload Document</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('agency.documents.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="hb-form-label">Document type</label>
                            <select name="document_type" class="hb-form-control" required>
                                <option value="">Select...</option>
                                <option value="license">License</option>
                                <option value="insurance">Insurance</option>
                                <option value="accreditation">Accreditation</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="hb-form-label">Expiry date (optional)</label>
                            <input type="date" name="expires_at" class="hb-form-control">
                        </div>
                        <div class="mb-2">
                            <label class="hb-form-label">File (PDF, JPG, PNG — max 8MB)</label>
                            <input type="file" name="file" class="hb-form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-agency-layout>
