<?php

namespace App\Policies;

use App\Models\Advisor;
use App\Models\Family;
use App\Models\Referral;
use App\Models\User;

/**
 * Governs the three parties in a Referral — Advisor (created it /
 * manages the Lead it came from), Family (it's their Care Seeker),
 * Agency (it was sent to them) — plus Super Admin/Platform Admin
 * oversight, mirroring the exact ownership-query pattern established by
 * LeadPolicy (Phase 11) and TourRequestPolicy (Phase 13). Agencies never
 * get update/manage abilities over anything except their own response
 * actions (accept/decline/tour/notes), enforced at the controller level
 * via separate, narrower Form Requests rather than a single broad
 * 'update' ability — see ReferralController's action-specific methods.
 */
class ReferralPolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        if ($actor->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $actor): bool
    {
        return $actor->can('leads.manage_all')
            || $actor->can('advisor.manage_assigned')
            || $actor->hasRole('family')
            || $actor->hasRole('agency_owner')
            || $actor->hasRole('agency_staff');
    }

    public function view(User $actor, Referral $referral): bool
    {
        return $this->advisorOwnedOrManaged($actor, $referral)
            || $this->familyOwned($actor, $referral)
            || $this->agencyOwned($actor, $referral)
            || $actor->can('leads.manage_all');
    }

    /**
     * Advisor-side management (add notes, set priority, cancel, resend,
     * progress the pipeline up to sending) — distinct from the agency's
     * own accept/decline/tour actions, which are authorized separately.
     */
    public function manageAsAdvisor(User $actor, Referral $referral): bool
    {
        return $this->advisorOwnedOrManaged($actor, $referral) || $actor->can('leads.manage_all');
    }

    /**
     * Agency-side response actions (accept, decline, request info, add
     * agency notes).
     */
    public function manageAsAgency(User $actor, Referral $referral): bool
    {
        return $this->agencyOwned($actor, $referral) || $actor->can('leads.manage_all');
    }

    private function advisorOwnedOrManaged(User $actor, Referral $referral): bool
    {
        $advisor = Advisor::where('user_id', $actor->id)->first();
        if (!$advisor) {
            return false;
        }

        if ($referral->advisor_id === $advisor->id) {
            return true;
        }

        return Advisor::where('id', $referral->advisor_id)
            ->where('advisor_manager_id', $advisor->id)
            ->exists();
    }

    private function familyOwned(User $actor, Referral $referral): bool
    {
        return Family::where('user_id', $actor->id)->where('id', $referral->family_id)->exists();
    }

    private function agencyOwned(User $actor, Referral $referral): bool
    {
        return $actor->id === $referral->agency?->user_id;
    }
}
