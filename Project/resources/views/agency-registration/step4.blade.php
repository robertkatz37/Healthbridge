<x-wizard-layout title="Agency Onboarding — Compliance" :current-step="4">
    <div class="mb-4">
        <h4 class="fw-bold mb-1" style="color:var(--hb-gray-900);font-family:'Fraunces',serif;">Certifications & documents</h4>
        <p style="color:var(--hb-gray-600);font-size:0.9rem;">These build trust with families. Optional, but recommended — you can add more later from your dashboard.</p>
    </div>

    {{-- Existing Certifications --}}
    @if($certifications->isNotEmpty())
        <div class="mb-4">
            <h6 class="fw-bold mb-2" style="color:var(--hb-gray-900);font-size:0.875rem;">Added Certifications</h6>
            @foreach($certifications as $cert)
                <div class="d-flex align-items-center justify-content-between p-2 mb-2" style="background:var(--hb-emerald-100);border-radius:0.625rem;">
                    <div>
                        <i class="bi bi-patch-check-fill me-2" style="color:var(--hb-emerald-700);"></i>
                        <span style="font-size:0.875rem;font-weight:600;">{{ $cert->name }}</span>
                        @if($cert->expires_at)
                            <span style="font-size:0.75rem;color:var(--hb-gray-600);">— expires {{ $cert->expires_at->format('M Y') }}</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('agency.certifications.destroy', $cert) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x-lg"></i></button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add Certification --}}
    <div class="card mb-4" style="border-radius:0.875rem;border:1.5px solid var(--hb-gray-200);">
        <div class="card-body p-3">
            <h6 class="fw-bold mb-3" style="font-size:0.875rem;color:var(--hb-gray-900);">Add a Certification</h6>
            <form method="POST" action="{{ route('agency.certifications.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" name="name" class="hb-form-control" placeholder="Certification name" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="issuing_body" class="hb-form-control" placeholder="Issuing body">
                    </div>
                    <div class="col-md-4">
                        <input type="date" name="issued_at" class="hb-form-control" placeholder="Issued date">
                    </div>
                    <div class="col-md-4">
                        <input type="date" name="expires_at" class="hb-form-control" placeholder="Expiry date">
                    </div>
                    <div class="col-md-4">
                        <input type="file" name="document" class="hb-form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
                <button type="submit" class="hb-btn-outline mt-3" style="width:auto;padding:0.5rem 1.25rem;font-size:0.85rem;">
                    <i class="bi bi-plus-lg me-1"></i>Add Certification
                </button>
            </form>
        </div>
    </div>

    <hr style="border-color:var(--hb-gray-200);margin:2rem 0;">

    {{-- Existing Documents --}}
    @if($documents->isNotEmpty())
        <div class="mb-4">
            <h6 class="fw-bold mb-2" style="color:var(--hb-gray-900);font-size:0.875rem;">Added Documents</h6>
            @foreach($documents as $doc)
                <div class="d-flex align-items-center justify-content-between p-2 mb-2" style="background:var(--hb-gray-50);border:1px solid var(--hb-gray-200);border-radius:0.625rem;">
                    <div>
                        <i class="bi bi-file-earmark-lock me-2" style="color:var(--hb-gray-600);"></i>
                        <span style="font-size:0.875rem;font-weight:600;">{{ ucfirst($doc->document_type->value) }}</span>
                        <span style="font-size:0.75rem;color:var(--hb-gray-600);">— {{ $doc->original_filename }}</span>
                    </div>
                    <form method="POST" action="{{ route('agency.documents.destroy', $doc) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x-lg"></i></button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add Document --}}
    <div class="card mb-4" style="border-radius:0.875rem;border:1.5px solid var(--hb-gray-200);">
        <div class="card-body p-3">
            <h6 class="fw-bold mb-3" style="font-size:0.875rem;color:var(--hb-gray-900);">Upload Insurance / License Document</h6>
            <form method="POST" action="{{ route('agency.documents.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4">
                        <select name="document_type" class="hb-form-control" required>
                            <option value="">Document type...</option>
                            <option value="license">License</option>
                            <option value="insurance">Insurance</option>
                            <option value="accreditation">Accreditation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="date" name="expires_at" class="hb-form-control" placeholder="Expiry date">
                    </div>
                    <div class="col-md-4">
                        <input type="file" name="file" class="hb-form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                </div>
                <button type="submit" class="hb-btn-outline mt-3" style="width:auto;padding:0.5rem 1.25rem;font-size:0.85rem;">
                    <i class="bi bi-upload me-1"></i>Upload Document
                </button>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('agency.register.step4.store') }}">
        @csrf
        <div class="d-flex justify-content-between">
            <a href="{{ route('agency.register.step3') }}" class="hb-btn-outline" style="width:auto;padding:0.7rem 1.75rem;text-decoration:none;">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
            <button type="submit" class="hb-btn-primary" style="width:auto;padding:0.7rem 2rem;">
                Continue <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </form>
</x-wizard-layout>
