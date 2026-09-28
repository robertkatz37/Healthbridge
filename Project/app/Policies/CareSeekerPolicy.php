<?php

namespace App\Policies;

use App\Models\Advisor;
use App\Models\CareSeeker;
use App\Models\Family;
use App\Models\Lead;
use App\Models\User;

class CareSeekerPolicy
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
        return $actor->can('care_seekers.manage_own')
            || $actor->can('families.manage_all')
            || $actor->can('advisor.manage_assigned');
    }

    public function view(User $actor, CareSeeker $careSeeker): bool
    {
        return $this->ownedByActor($actor, $careSeeker)
            || $actor->can('families.manage_all')
            || $this->assignedAdvisorOrManager($actor, $careSeeker);
    }

    public function create(User $actor): bool
    {
        return $actor->can('care_seekers.manage_own') || $actor->can('families.manage_all');
    }

    public function update(User $actor, CareSeeker $careSeeker): bool
    {
        return $this->ownedByActor($actor, $careSeeker) || $actor->can('families.manage_all');
    }

    public function delete(User $actor, CareSeeker $careSeeker): bool
    {
        return $this->ownedByActor($actor, $careSeeker) || $actor->can('families.manage_all');
    }

    private function ownedByActor(User $actor, CareSeeker $careSeeker): bool
    {
        // Queries directly rather than $actor->family — see
        // DATABASE_DECISIONS.md §13 for why relation-property access is
        // avoided in policy checks that may run multiple times against
        // the same User instance within one request/test lifecycle.
        return Family::where('user_id', $actor->id)->where('id', $careSeeker->family_id)->exists();
    }

    /**
     * True if the actor is the advisor currently assigned to a Lead for
     * this Care Seeker (or that advisor's manager) — mirrors
     * LeadPolicy's ownership check exactly. Tightened during Phase 12:
     * this previously granted view access to *any* advisor via a bare
     * `advisor.manage_assigned` permission check (a role-level
     * permission, not an ownership check) — a gap from Phase 9, before
     * the Lead entity existed to check real assignment against. Phase 12
     * ties match scores and recommendations to Care Seeker data, making
     * that gap worth closing now rather than carrying it forward. See
     * DATABASE_DECISIONS.md.
     */
    private function assignedAdvisorOrManager(User $actor, CareSeeker $careSeeker): bool
    {
        $advisor = Advisor::where('user_id', $actor->id)->first();
        if (!$advisor) {
            return false;
        }

        $leadAdvisorIds = Lead::where('care_seeker_id', $careSeeker->id)
            ->whereNotNull('advisor_id')
            ->pluck('advisor_id');

        if ($leadAdvisorIds->contains($advisor->id)) {
            return true;
        }

        return Advisor::whereIn('id', $leadAdvisorIds)
            ->where('advisor_manager_id', $advisor->id)
            ->exists();
    }
}
