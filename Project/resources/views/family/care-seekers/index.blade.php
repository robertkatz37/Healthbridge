<x-family-layout title="Care Seekers">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Care Seekers</li>
    @endslot

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Care Seeker Profiles</h6>
        <a href="{{ route('family.care-seekers.create') }}" class="btn btn-primary btn-sm" style="border-radius:0.625rem;">
            <i class="bi bi-plus-lg me-1"></i> Add Care Seeker
        </a>
    </div>

    @if($careSeekers->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-heart" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-3" style="color:var(--hb-gray-600);">
                    No care seeker profiles yet. Add one to begin your search.
                </p>
                <a href="{{ route('family.care-seekers.create') }}" class="btn btn-primary" style="border-radius:0.75rem;">
                    Add Your First Care Seeker
                </a>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($careSeekers as $seeker)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="{{ $seeker->photo_url }}" alt="{{ $seeker->full_name }}" style="width:52px;height:52px;border-radius:50%;object-fit:cover;">
                                <div>
                                    <div class="fw-bold" style="color:var(--hb-gray-900);">{{ $seeker->full_name }}</div>
                                    @if($seeker->age)
                                        <div style="font-size:0.775rem;color:var(--hb-gray-600);">Age {{ $seeker->age }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-1 mb-3">
                                @if($seeker->care_type_needed)
                                    <span class="hb-badge-verified">{{ $seeker->care_type_needed->label() }}</span>
                                @endif
                                @if($seeker->completed_assessments_count > 0)
                                    <span style="font-size:0.7rem;font-weight:600;background:var(--hb-emerald-100);color:var(--hb-emerald-700);padding:0.2rem 0.6rem;border-radius:999px;">
                                        <i class="bi bi-check2-circle me-1"></i>Assessment Done
                                    </span>
                                @endif
                            </div>

                            <div class="d-flex gap-2 mb-2">
                                <a href="{{ route('family.care-seekers.edit', $seeker) }}" class="btn btn-sm btn-outline-primary flex-fill" style="border-radius:0.5rem;">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </a>
                                <a href="{{ route('family.needs-assessment.start', $seeker) }}" class="btn btn-sm btn-outline-secondary flex-fill" style="border-radius:0.5rem;">
                                    <i class="bi bi-clipboard2-pulse me-1"></i>Assessment
                                </a>
                            </div>
                            @if($seeker->completed_assessments_count > 0)
                                <a href="{{ route('family.care-seekers.recommendations.index', $seeker) }}" class="btn btn-sm btn-primary w-100" style="border-radius:0.5rem;">
                                    <i class="bi bi-stars me-1"></i>Recommended Agencies
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-family-layout>
