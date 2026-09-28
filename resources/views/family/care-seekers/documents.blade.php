<x-family-layout title="Documents — {{ $careSeeker->full_name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('family.care-seekers.index') }}" class="hb-link">Care Seekers</a></li>
        <li class="breadcrumb-item"><a href="{{ route('family.care-seekers.edit', $careSeeker) }}" class="hb-link">{{ $careSeeker->full_name }}</a></li>
        <li class="breadcrumb-item active">Documents</li>
    @endslot

    <div class="hb-alert hb-alert-info mb-3">
        <i class="bi bi-shield-lock me-2"></i>
        These documents are private and only visible to you.
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Documents for {{ $careSeeker->full_name }}</h6>
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
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Uploaded</th>
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
                                    <td class="px-4 py-3">{{ $doc->document_type->label() }}</td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $doc->created_at->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('family.care-seekers.documents.download', [$careSeeker, $doc]) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <form method="POST" action="{{ route('family.care-seekers.documents.destroy', [$careSeeker, $doc]) }}" class="d-inline" onsubmit="return confirm('Remove this document?')">
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
                <form method="POST" action="{{ route('family.care-seekers.documents.store', $careSeeker) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="hb-form-label">Document type</label>
                            <select name="document_type" class="hb-form-control" required>
                                <option value="">Select...</option>
                                <option value="medical_record">Medical Record</option>
                                <option value="insurance_card">Insurance Card</option>
                                <option value="poa_document">Power of Attorney</option>
                                <option value="other">Other</option>
                            </select>
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
</x-family-layout>
