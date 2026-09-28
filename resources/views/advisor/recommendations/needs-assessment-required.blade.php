<x-advisor-layout title="Recommendations">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('advisor.leads.show', $lead) }}" class="hb-link">{{ $lead->family_name }}</a></li>
        <li class="breadcrumb-item active">Recommendations</li>
    @endslot

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard2-pulse" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
            <h6 class="fw-bold mt-3 mb-2" style="color:var(--hb-gray-900);">No Completed Needs Assessment</h6>
            <p style="font-size:0.9rem;color:var(--hb-gray-600);max-width:420px;margin:0 auto;">
                Matching is based on the family's completed Needs Assessment. Ask the family to complete it from their dashboard before generating recommendations.
            </p>
        </div>
    </div>
</x-advisor-layout>
