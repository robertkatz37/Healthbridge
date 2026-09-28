<x-wizard-layout title="Agency Onboarding — Review & Submit" :current-step="5">
    <div class="mb-4">
        <h4 class="fw-bold mb-1" style="color:var(--hb-gray-900);font-family:'Fraunces',serif;">Add photos & review your listing</h4>
        <p style="color:var(--hb-gray-600);font-size:0.9rem;">Add at least one photo, then review everything before submitting for approval.</p>
    </div>

    {{-- Media Gallery --}}
    <h6 class="fw-bold mb-2" style="color:var(--hb-gray-900);font-size:0.875rem;"><i class="bi bi-images me-2"></i>Photos</h6>

    @if($agency->media->isNotEmpty())
        <div class="row g-2 mb-3">
            @foreach($agency->media as $item)
                <div class="col-4 col-md-3">
                    <div style="position:relative;border-radius:0.625rem;overflow:hidden;aspect-ratio:1;">
                        @if($item->type->value === 'photo')
                            <img src="{{ asset('storage/' . $item->path) }}" alt="{{ $item->caption }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <div style="width:100%;height:100%;background:var(--hb-gray-100);display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-camera-video" style="font-size:1.5rem;color:var(--hb-gray-600);"></i>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('agency.media.destroy', $item) }}" style="position:absolute;top:4px;right:4px;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm" style="background:rgba(0,0,0,0.6);color:white;border-radius:50%;width:24px;height:24px;padding:0;line-height:1;">
                                <i class="bi bi-x" style="font-size:0.8rem;"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="card mb-4" style="border-radius:0.875rem;border:1.5px solid var(--hb-gray-200);">
        <div class="card-body p-3">
            <form method="POST" action="{{ route('agency.media.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" value="photo">
                <div class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <input type="file" name="file" class="hb-form-control" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="hb-btn-outline w-100" style="padding:0.65rem;font-size:0.85rem;">
                            <i class="bi bi-upload me-1"></i>Upload Photo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <hr style="border-color:var(--hb-gray-200);margin:2rem 0;">

    {{-- Review Summary --}}
    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);font-size:0.875rem;"><i class="bi bi-clipboard-check me-2"></i>Review Your Listing</h6>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div style="background:var(--hb-gray-50);border-radius:0.75rem;padding:1rem;">
                <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;letter-spacing:0.05em;">Agency</div>
                <div style="font-size:0.9rem;font-weight:600;color:var(--hb-gray-900);">{{ $agency->name }}</div>
                <div style="font-size:0.8rem;color:var(--hb-gray-600);">{{ $agency->category->name ?? '—' }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div style="background:var(--hb-gray-50);border-radius:0.75rem;padding:1rem;">
                <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;letter-spacing:0.05em;">Location</div>
                <div style="font-size:0.9rem;font-weight:600;color:var(--hb-gray-900);">{{ $agency->city }}, {{ $agency->state }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div style="background:var(--hb-gray-50);border-radius:0.75rem;padding:1rem;text-align:center;">
                <div style="font-size:1.5rem;font-weight:700;color:var(--hb-emerald-700);">{{ $agency->services->count() }}</div>
                <div style="font-size:0.75rem;color:var(--hb-gray-600);">Services</div>
            </div>
        </div>
        <div class="col-md-4">
            <div style="background:var(--hb-gray-50);border-radius:0.75rem;padding:1rem;text-align:center;">
                <div style="font-size:1.5rem;font-weight:700;color:var(--hb-emerald-700);">{{ $agency->coverage->count() }}</div>
                <div style="font-size:0.75rem;color:var(--hb-gray-600);">Coverage Areas</div>
            </div>
        </div>
        <div class="col-md-4">
            <div style="background:var(--hb-gray-50);border-radius:0.75rem;padding:1rem;text-align:center;">
                <div style="font-size:1.5rem;font-weight:700;color:var(--hb-emerald-700);">{{ $agency->certifications->count() }}</div>
                <div style="font-size:0.75rem;color:var(--hb-gray-600);">Certifications</div>
            </div>
        </div>
    </div>

    @if(!empty($readinessErrors))
        <div class="hb-alert hb-alert-warning mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <div>
                <strong>Before submitting, please:</strong>
                <ul class="mb-0 mt-1" style="padding-left:1.25rem;">
                    @foreach($readinessErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="hb-alert hb-alert-info mb-4">
        <i class="bi bi-info-circle me-2"></i>
        Once submitted, your listing will be reviewed by our team before going live. This typically takes 1-2 business days.
    </div>

    <form method="POST" action="{{ route('agency.register.complete') }}">
        @csrf
        <div class="d-flex justify-content-between">
            <a href="{{ route('agency.register.step4') }}" class="hb-btn-outline" style="width:auto;padding:0.7rem 1.75rem;text-decoration:none;">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
            <button type="submit" class="hb-btn-primary" style="width:auto;padding:0.7rem 2rem;" {{ !empty($readinessErrors) ? 'disabled' : '' }}>
                <i class="bi bi-send me-2"></i>Submit for Review
            </button>
        </div>
    </form>
</x-wizard-layout>
