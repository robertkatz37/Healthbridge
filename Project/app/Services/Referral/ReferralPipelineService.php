<?php

namespace App\Services\Referral;

use App\Enums\ReferralStatus;
use App\Models\Referral;
use App\Models\User;
use App\Notifications\Referral\MoveInConfirmed;
use App\Notifications\Referral\ReferralAccepted;
use App\Notifications\Referral\ReferralClosed;
use App\Notifications\Referral\ReferralDeclined;
use App\Notifications\Referral\ReferralSent;
use Illuminate\Validation\ValidationException;

/**
 * Owns every Referral pipeline stage transition — mirrors
 * LeadPipelineService (Phase 11) exactly: validate against
 * ReferralStatus::allowedNextStatuses() (plus the separate
 * canCancel()/canCloseAsLost() escape hatches), write a typed
 * referral_status_history row, log to the general activity_log, update
 * referrals.status, stamp the relevant timestamp column, and dispatch
 * the matching notification.
 */
class ReferralPipelineService
{
    public function transition(Referral $referral, ReferralStatus $to, ?User $actor = null, ?string $reason = null): void
    {
        $from = $referral->status;

        $isValidProgression = in_array($to, $from->allowedNextStatuses(), true);
        $isValidCancel = $to === ReferralStatus::Cancelled && $from->canCancel();
        $isValidCloseLost = $to === ReferralStatus::ClosedLost && $from->canCloseAsLost();

        if (!$isValidProgression && !$isValidCancel && !$isValidCloseLost) {
            throw ValidationException::withMessages([
                'status' => "Cannot move referral from {$from->label()} to {$to->label()}.",
            ]);
        }

        if (($to === ReferralStatus::ClosedLost || $to === ReferralStatus::Cancelled || $to === ReferralStatus::AgencyDeclined) && !$reason) {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for this transition.',
            ]);
        }

        $referral->statusHistory()->create([
            'from_status' => $from->value,
            'to_status' => $to->value,
            'reason' => $reason,
            'changed_by' => $actor?->id,
        ]);

        $updates = ['status' => $to->value];

        if ($to === ReferralStatus::SentToAgency) {
            $updates['sent_at'] = now();
        }

        if ($to === ReferralStatus::AgencyAccepted || $to === ReferralStatus::AgencyDeclined) {
            $updates['agency_responded_at'] = now();
        }

        if ($to === ReferralStatus::Converted) {
            $updates['converted_at'] = now();
        }

        if (in_array($to, [ReferralStatus::ClosedLost, ReferralStatus::Cancelled, ReferralStatus::AgencyDeclined], true)) {
            $updates['closed_at'] = now();
            $updates['closed_reason'] = $reason;
        }

        $referral->update($updates);

        activity()
            ->causedBy($actor)
            ->performedOn($referral)
            ->withProperties(array_filter(['reason' => $reason]))
            ->log("Referral moved to {$to->label()}");

        $this->notify($referral, $to);
    }

    private function notify(Referral $referral, ReferralStatus $to): void
    {
        $familyUser = $referral->family->user;
        $advisorUser = $referral->advisor->user;

        match ($to) {
            ReferralStatus::SentToAgency => $referral->agency->user?->notify(new ReferralSent($referral)),
            ReferralStatus::AgencyAccepted => collect([$familyUser, $advisorUser])->each(fn ($u) => $u->notify(new ReferralAccepted($referral))),
            ReferralStatus::AgencyDeclined => collect([$familyUser, $advisorUser])->each(fn ($u) => $u->notify(new ReferralDeclined($referral))),
            ReferralStatus::MoveInConfirmed => collect([$familyUser, $advisorUser, $referral->agency->user])->filter()->each(fn ($u) => $u->notify(new MoveInConfirmed($referral))),
            ReferralStatus::Converted, ReferralStatus::ClosedLost, ReferralStatus::Cancelled => collect([$familyUser, $advisorUser])->each(fn ($u) => $u->notify(new ReferralClosed($referral))),
            default => null,
        };
    }
}
