<?php

namespace App\Services\Agency;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\AgencyAdminNote;
use App\Models\User;
use App\Notifications\Agency\AgencyReactivated;
use App\Notifications\Agency\AgencySuspended;
use App\Notifications\Agency\ApplicationApproved;
use App\Notifications\Agency\ApplicationRejected;
use App\Notifications\Agency\ChangesRequested as ChangesRequestedNotification;
use Illuminate\Validation\ValidationException;

/**
 * Owns every Agency moderation state transition. Every method here:
 *  1. Validates the transition is legal per AgencyStatus::allowedNextStatuses()
 *  2. Writes an agency_status_history row (typed audit trail)
 *  3. Fires an activity() log entry (general audit trail, per SRS FR-40)
 *  4. Sends the relevant owner-facing email/database notification
 *  5. Updates agencies.status
 *
 * Controllers stay thin and never touch $agency->status directly for a
 * moderation action — see Admin\AgencyController.
 */
class AgencyModerationService
{
    /**
     * Approve moves PendingReview → Approved → Published in one atomic
     * admin action (no separate manual "Publish" step exists) — see
     * AgencyStatus's docblock and DATABASE_DECISIONS.md for the reasoning.
     * Both transitions are recorded in status history for full auditability.
     */
    public function approve(Agency $agency, User $admin): void
    {
        $this->assertTransitionAllowed($agency, AgencyStatus::Approved);
        $this->recordTransition($agency, AgencyStatus::Approved, $admin);

        // $agency->status now reflects 'approved' — update() refreshes the
        // model's own attributes in-memory (unlike relation properties,
        // plain columns aren't lazy-cached, so no re-fetch is needed here).
        $this->assertTransitionAllowed($agency, AgencyStatus::Published);
        $this->recordTransition($agency, AgencyStatus::Published, $admin);

        activity()->causedBy($admin)->performedOn($agency)->log('Agency approved and published');

        $agency->user->notify(new ApplicationApproved($agency));
    }

    public function reject(Agency $agency, User $admin, string $reason): void
    {
        $this->assertTransitionAllowed($agency, AgencyStatus::Rejected);
        $this->recordTransition($agency, AgencyStatus::Rejected, $admin, $reason);

        activity()->causedBy($admin)->performedOn($agency)
            ->withProperties(['reason' => $reason])
            ->log('Agency application rejected');

        $agency->user->notify(new ApplicationRejected($agency, $reason));
    }

    public function requestChanges(Agency $agency, User $admin, string $notes): void
    {
        $this->assertTransitionAllowed($agency, AgencyStatus::ChangesRequested);
        $this->recordTransition($agency, AgencyStatus::ChangesRequested, $admin, $notes);

        activity()->causedBy($admin)->performedOn($agency)
            ->withProperties(['notes' => $notes])
            ->log('Changes requested on agency application');

        $agency->user->notify(new ChangesRequestedNotification($agency, $notes));
    }

    public function suspend(Agency $agency, User $admin, string $reason): void
    {
        $this->assertTransitionAllowed($agency, AgencyStatus::Suspended);
        $this->recordTransition($agency, AgencyStatus::Suspended, $admin, $reason);

        activity()->causedBy($admin)->performedOn($agency)
            ->withProperties(['reason' => $reason])
            ->log('Agency suspended');

        $agency->user->notify(new AgencySuspended($agency, $reason));
    }

    public function reactivate(Agency $agency, User $admin): void
    {
        $this->assertTransitionAllowed($agency, AgencyStatus::Published);
        $this->recordTransition($agency, AgencyStatus::Published, $admin);

        activity()->causedBy($admin)->performedOn($agency)->log('Agency reactivated');

        $agency->user->notify(new AgencyReactivated($agency));
    }

    public function addNote(Agency $agency, User $author, string $note): AgencyAdminNote
    {
        $created = $agency->adminNotes()->create([
            'user_id' => $author->id,
            'note' => $note,
        ]);

        activity()->causedBy($author)->performedOn($agency)->log('Internal admin note added');

        return $created;
    }

    private function assertTransitionAllowed(Agency $agency, AgencyStatus $to): void
    {
        $from = $agency->status;

        if (!in_array($to, $from->allowedNextStatuses(), true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition agency from {$from->label()} to {$to->label()}.",
            ]);
        }
    }

    private function recordTransition(Agency $agency, AgencyStatus $to, User $admin, ?string $reason = null): void
    {
        $from = $agency->status;

        $agency->statusHistory()->create([
            'from_status' => $from->value,
            'to_status' => $to->value,
            'reason' => $reason,
            'changed_by' => $admin->id,
        ]);

        $agency->update(['status' => $to->value]);
    }
}
