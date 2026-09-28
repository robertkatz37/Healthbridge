<x-family-layout title="Needs Assessment">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Needs Assessment</li>
    @endslot

    @if($drafts->isNotEmpty())
        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">In Progress</h6>
        <div class="row g-3 mb-4">
            @foreach($drafts as $draft)
                <div class="col-md-6">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="fw-bold" style="color:var(--hb-gray-900);">{{ $draft->careSeeker->full_name }}</div>
                                <span style="font-size:0.7rem;font-weight:600;background:#FFFBEB;color:#92400E;padding:0.2rem 0.6rem;border-radius:999px;">
                                    Step {{ $draft->current_step }} of {{ \App\Services\Family\NeedsAssessmentService::TOTAL_STEPS }}
                                </span>
                            </div>
                            <div class="progress mb-3" style="height:6px;border-radius:999px;">
                                <div class="progress-bar" style="width:{{ ($draft->current_step / \App\Services\Family\NeedsAssessmentService::TOTAL_STEPS) * 100 }}%;background:var(--hb-emerald-700);"></div>
                            </div>
                            <a href="{{ route('family.needs-assessment.start', $draft->careSeeker) }}" class="btn btn-primary btn-sm" style="border-radius:0.625rem;">
                                <i class="bi bi-play-fill me-1"></i>Resume
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Completed Assessments</h6>
    @if($completed->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-clipboard2-pulse" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No completed assessments yet.</p>
            </div>
        </div>
    @else
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-0">
                <table class="table mb-0" style="font-size:0.875rem;">
                    <thead style="background:var(--hb-gray-50);">
                        <tr>
                            <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Care Seeker</th>
                            <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Completed</th>
                            <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($completed as $assessment)
                            <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                <td class="px-4 py-3 fw-bold" style="color:var(--hb-gray-900);">{{ $assessment->careSeeker->full_name }}</td>
                                <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $assessment->completed_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-end">
                                    <a href="{{ route('family.needs-assessment.start', $assessment->careSeeker) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">
                                        <i class="bi bi-arrow-repeat me-1"></i>Retake
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-family-layout>
