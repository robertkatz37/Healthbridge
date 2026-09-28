<x-family-layout title="My Shortlist">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">My Shortlist</li>
    @endslot

    <div class="mb-3">
        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Shortlisted Agencies</h6>
        <span style="font-size:0.8rem;color:var(--hb-gray-600);">{{ $shortlisted->count() }} {{ Str::plural('agency', $shortlisted->count()) }} across all care seekers</span>
    </div>

    @if($shortlisted->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-star" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-3" style="color:var(--hb-gray-600);">
                    You haven't shortlisted any agencies yet. Add agencies to your shortlist from the Recommendations page.
                </p>
                <a href="{{ route('family.care-seekers.index') }}" class="btn btn-primary" style="border-radius:0.75rem;">
                    View Care Seekers
                </a>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($shortlisted as $result)
                @php $agency = $result->agency; $careSeeker = $result->needsAssessment->careSeeker; @endphp
                <div class="col-lg-6">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <a href="{{ route('agencies.show', $agency) }}" class="text-decoration-none">
                                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">{{ $agency->name }}</h6>
                                    </a>
                                    <div style="font-size:0.8rem;color:var(--hb-gray-600);">
                                        <i class="bi bi-geo-alt me-1"></i>{{ $agency->city }}, {{ $agency->state }}
                                    </div>
                                </div>
                                <div style="font-size:1.3rem;font-weight:800;color:var(--hb-emerald-700);">{{ round($result->compatibility_score) }}%</div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="hb-badge-verified">{{ $agency->category?->name }}</span>
                                <span style="font-size:0.7rem;font-weight:600;background:var(--hb-emerald-100);color:var(--hb-emerald-700);padding:0.2rem 0.6rem;border-radius:999px;">
                                    For {{ $careSeeker->full_name }}
                                </span>
                            </div>
                            <a href="{{ route('family.care-seekers.recommendations.index', $careSeeker) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">
                                <i class="bi bi-stars me-1"></i>View in Recommendations
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-family-layout>
