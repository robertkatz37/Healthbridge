<x-wizard-layout title="Review & Submit" :current-step="$step">

    <div class="mb-4">
        <h4 class="fw-bold mb-1" style="color:var(--hb-gray-900);font-family:'Fraunces',serif;">Review &amp; Submit</h4>
        <p style="color:var(--hb-gray-600);font-size:0.9rem;">
            Review your answers for {{ $careSeeker->full_name }}, then submit. You can update this anytime from their profile.
        </p>
    </div>

    @php
        $sections = [
            'care_type' => 'Care Type', 'timeline' => 'Timeline', 'budget' => 'Budget',
            'location' => 'Location', 'medical' => 'Medical Conditions', 'mobility' => 'Mobility',
            'memory' => 'Memory Care', 'adls' => 'Daily Living', 'behavioral' => 'Behavioral',
            'languages' => 'Languages', 'insurance' => 'Insurance & Benefits',
        ];
        $answersBySection = $assessment->answers()->with('question')->get()->groupBy(fn($a) => $a->question->section ?? 'other');
    @endphp

    @forelse($answersBySection as $section => $answers)
        <div class="mb-3 p-3" style="background:var(--hb-gray-50);border-radius:0.75rem;">
            <div style="font-size:0.7rem;font-weight:700;color:var(--hb-emerald-700);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.5rem;">
                {{ $sections[$section] ?? ucfirst($section) }}
            </div>
            @foreach($answers as $answer)
                <div style="font-size:0.875rem;color:var(--hb-gray-900);padding:0.15rem 0;">
                    <span style="color:var(--hb-gray-600);">{{ $answer->question->question_text }}</span>
                    — <strong>{{ is_array($answer->answer_value) ? implode(', ', $answer->answer_value) : $answer->answer_value }}</strong>
                </div>
            @endforeach
        </div>
    @empty
        <div class="hb-alert hb-alert-warning mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            No answers recorded yet. Go back and complete at least one section before submitting.
        </div>
    @endforelse

    <form method="POST" action="{{ route('family.needs-assessment.complete', $careSeeker) }}" class="mt-4">
        @csrf
        <div class="d-flex justify-content-between">
            <a href="{{ route('family.needs-assessment.step', [$careSeeker, $step - 1]) }}" class="hb-btn-outline" style="width:auto;padding:0.7rem 1.75rem;text-decoration:none;">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
            <button type="submit" class="hb-btn-primary" style="width:auto;padding:0.7rem 2rem;" {{ $answersBySection->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-check2-circle me-2"></i>Complete Assessment
            </button>
        </div>
    </form>
</x-wizard-layout>
