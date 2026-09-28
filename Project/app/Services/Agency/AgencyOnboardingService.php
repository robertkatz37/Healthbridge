<?php

namespace App\Services\Agency;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use Illuminate\Validation\ValidationException;

/**
 * Manages progression through the 5-step Agency onboarding wizard.
 * Each step's data is persisted immediately on submission (not just
 * held in session) so progress survives a browser close — the
 * "autosave" requirement from PROJECT_ROADMAP.md Phase 7 TODO is
 * satisfied by writing to the database on every step rather than a
 * client-side draft mechanism, since Agency (and its related rows)
 * already exist in the database from Step 1 onward.
 */
class AgencyOnboardingService
{
    public function advanceStep(Agency $agency, int $completedStep): void
    {
        $nextStep = $completedStep + 1;

        if ($agency->onboarding_step < $nextStep) {
            $agency->update(['onboarding_step' => min($nextStep, 5)]);
        }
    }

    /**
     * Submits the agency for review — legal from Draft (first submission)
     * or ChangesRequested (owner resubmitting after admin feedback, see
     * AgencyModerationService). Both transitions land on PendingReview.
     * Uses AgencyStatus's own state machine (not a separate check) so this
     * stays consistent with AgencyModerationService's transitions rather
     * than risking the two drifting apart.
     */
    public function complete(Agency $agency): void
    {
        if (!in_array(AgencyStatus::PendingReview, $agency->status->allowedNextStatuses(), true)) {
            throw ValidationException::withMessages([
                'submission' => "Cannot submit for review from the current status ({$agency->status->label()}).",
            ]);
        }

        $agency->statusHistory()->create([
            'from_status' => $agency->status->value,
            'to_status' => AgencyStatus::PendingReview->value,
            'changed_by' => $agency->user_id,
        ]);

        $agency->update([
            'status' => AgencyStatus::PendingReview->value,
            'onboarding_completed_at' => now(),
        ]);
    }

    /**
     * Which step the wizard should resume at when the owner returns.
     * Clamped 1-5; onboarding_step may exceed 5 internally as a "complete" marker.
     */
    public function resumeStep(Agency $agency): int
    {
        return min(max($agency->onboarding_step, 1), 5);
    }

    /**
     * Minimum readiness check before allowing submission at Step 5:
     * name/category (step 1), at least one service (step 2), at least
     * one coverage area (step 3). Hours/certifications/media are optional.
     */
    public function readinessErrors(Agency $agency): array
    {
        $errors = [];

        if (!$agency->name || !$agency->agency_category_id) {
            $errors[] = 'Basic business information is incomplete.';
        }

        if ($agency->services()->count() === 0) {
            $errors[] = 'Add at least one service before submitting for review.';
        }

        if ($agency->coverage()->count() === 0) {
            $errors[] = 'Add at least one coverage area before submitting for review.';
        }

        return $errors;
    }
}
