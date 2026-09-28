<x-family-layout title="Recommended Agencies">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('family.care-seekers.edit', $careSeeker) }}" class="hb-link">{{ $careSeeker->full_name }}</a></li>
        <li class="breadcrumb-item active">Recommendations</li>
    @endslot

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard2-pulse" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
            <h6 class="fw-bold mt-3 mb-2" style="color:var(--hb-gray-900);">Complete the Needs Assessment First</h6>
            <p style="font-size:0.9rem;color:var(--hb-gray-600);max-width:420px;margin:0 auto 1.5rem;">
                Personalized agency recommendations for {{ $careSeeker->full_name }} are based on the completed Needs Assessment.
            </p>
            <a href="{{ route('family.needs-assessment.start', $careSeeker) }}" class="btn btn-primary" style="border-radius:0.75rem;">
                <i class="bi bi-play-fill me-2"></i>Start Needs Assessment
            </a>
        </div>
    </div>
</x-family-layout>
