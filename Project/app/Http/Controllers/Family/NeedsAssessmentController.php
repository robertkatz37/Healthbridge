<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\SubmitAssessmentStepRequest;
use App\Models\CareSeeker;
use App\Models\Family;
use App\Models\NeedsAssessment;
use App\Services\Family\NeedsAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NeedsAssessmentController extends Controller
{
    public function __construct(
        private readonly NeedsAssessmentService $assessments,
    ) {}

    /**
     * Drafts list — every in-progress assessment across this family's
     * care seekers, satisfying "Saved Drafts" / "Resume Assessment".
     */
    public function drafts(Request $request): View
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();

        $drafts = NeedsAssessment::whereIn('care_seeker_id', $family->careSeekers()->pluck('id'))
            ->where('status', 'in_progress')
            ->with('careSeeker')
            ->latest()
            ->get();

        $completed = NeedsAssessment::whereIn('care_seeker_id', $family->careSeekers()->pluck('id'))
            ->where('status', 'completed')
            ->with('careSeeker')
            ->latest('completed_at')
            ->get();

        return view('family.needs-assessment.drafts', compact('drafts', 'completed'));
    }

    public function start(Request $request, CareSeeker $careSeeker): RedirectResponse
    {
        $this->authorize('update', $careSeeker);

        $assessment = $this->assessments->startOrResume($careSeeker);
        $step = $this->assessments->resumeStep($assessment);

        return redirect()->route('family.needs-assessment.step', [$careSeeker, $step]);
    }

    public function step(Request $request, CareSeeker $careSeeker, int $step): View|RedirectResponse
    {
        $this->authorize('view', $careSeeker);

        if ($step < 1 || $step > NeedsAssessmentService::TOTAL_STEPS) {
            abort(404);
        }

        $assessment = $this->assessments->startOrResume($careSeeker);
        $questions = $this->assessments->questionsForStep($step);
        $existingAnswers = $this->assessments->existingAnswers($assessment, $step);

        if ($step === NeedsAssessmentService::TOTAL_STEPS) {
            $careSeeker->load('needsAssessments');
            return view('family.needs-assessment.review', compact('careSeeker', 'assessment', 'step'));
        }

        return view('family.needs-assessment.step', compact('careSeeker', 'assessment', 'step', 'questions', 'existingAnswers'));
    }

    public function storeStep(SubmitAssessmentStepRequest $request, CareSeeker $careSeeker, int $step): RedirectResponse
    {
        $assessment = $this->assessments->startOrResume($careSeeker);

        $this->assessments->saveStepAnswers($assessment, $step, $request->input('answers', []));

        $nextStep = min($step + 1, NeedsAssessmentService::TOTAL_STEPS);

        return redirect()->route('family.needs-assessment.step', [$careSeeker, $nextStep])
            ->with('status', 'step-saved');
    }

    public function complete(Request $request, CareSeeker $careSeeker): RedirectResponse
    {
        $this->authorize('update', $careSeeker);

        $assessment = $this->assessments->startOrResume($careSeeker);
        $this->assessments->complete($assessment);

        activity()->causedBy($request->user())->performedOn($careSeeker)->log('Needs assessment completed');

        return redirect()->route('family.care-seekers.edit', $careSeeker)
            ->with('status', 'assessment-completed');
    }
}
