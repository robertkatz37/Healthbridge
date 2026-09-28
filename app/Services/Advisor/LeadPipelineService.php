<?php

namespace App\Services\Advisor;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Owns every Lead pipeline stage transition — mirrors
 * AgencyModerationService's pattern (Phase 8): validate the transition
 * against LeadStatus::allowedNextStatuses(), write a typed
 * lead_status_history row, log to the general activity_log, update
 * leads.status. Converting/closing also stamp the relevant timestamp
 * column.
 */
class LeadPipelineService
{
    public function transition(Lead $lead, LeadStatus $to, ?User $actor = null, ?string $reason = null): void
    {
        $from = $lead->status;

        $isValidProgression = in_array($to, $from->allowedNextStatuses(), true);
        $isValidCloseLost = $to === LeadStatus::ClosedLost && $from->canCloseAsLost();

        if (!$isValidProgression && !$isValidCloseLost) {
            throw ValidationException::withMessages([
                'status' => "Cannot move lead from {$from->label()} to {$to->label()}.",
            ]);
        }

        $lead->statusHistory()->create([
            'from_status' => $from->value,
            'to_status' => $to->value,
            'reason' => $reason,
            'changed_by' => $actor?->id,
        ]);

        $updates = ['status' => $to->value];

        if ($to === LeadStatus::Converted) {
            $updates['converted_at'] = now();
        }

        if ($to === LeadStatus::ClosedLost) {
            $updates['closed_at'] = now();
            $updates['closed_reason'] = $reason;
        }

        $lead->update($updates);

        activity()
            ->causedBy($actor)
            ->performedOn($lead)
            ->withProperties(array_filter(['reason' => $reason]))
            ->log("Lead moved to {$to->label()}");
    }
}
