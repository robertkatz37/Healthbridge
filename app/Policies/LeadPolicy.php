<?php

namespace App\Policies;

use App\Models\Advisor;
use App\Models\Family;
use App\Models\Lead;
use App\Models\User;

class LeadPolicy
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
        return $actor->can('advisor.manage_assigned') || $actor->can('leads.manage_all');
    }

    public function view(User $actor, Lead $lead): bool
    {
        return $this->ownedByActor($actor, $lead)
            || $this->onActorsTeam($actor, $lead)
            || $actor->can('leads.manage_all');
    }

    public function update(User $actor, Lead $lead): bool
    {
        return $this->ownedByActor($actor, $lead)
            || $this->onActorsTeam($actor, $lead)
            || $actor->can('leads.manage_all');
    }

    /**
     * Deliberately narrower than update() — a family can read/send
     * messages on their own Lead's conversation ("Advisor Messages" on
     * the Family Dashboard, Phase 13) without gaining any of the
     * broader Lead-management abilities update() grants to advisors.
     */
    public function message(User $actor, Lead $lead): bool
    {
        return Family::where('user_id', $actor->id)->where('id', $lead->family_id)->exists()
            || $this->update($actor, $lead);
    }

    /**
     * Manual (re)assignment — Super Admin/Platform Admin always, plus an
     * Advisor Manager reassigning within their own team.
     */
    public function assign(User $actor, Lead $lead): bool
    {
        return $actor->can('leads.manage_all') || $actor->can('advisor.manage_team');
    }

    private function ownedByActor(User $actor, Lead $lead): bool
    {
        $advisor = Advisor::where('user_id', $actor->id)->first();

        return $advisor && $lead->advisor_id === $advisor->id;
    }

    /**
     * True if the lead's assigned advisor reports to the actor (the actor
     * is that advisor's manager) — lets an Advisor Manager view/update
     * their team's leads, not just their own.
     */
    private function onActorsTeam(User $actor, Lead $lead): bool
    {
        if (!$lead->advisor_id) {
            return false;
        }

        $managerAdvisor = Advisor::where('user_id', $actor->id)->first();
        if (!$managerAdvisor) {
            return false;
        }

        return \App\Models\Advisor::where('id', $lead->advisor_id)
            ->where('advisor_manager_id', $managerAdvisor->id)
            ->exists();
    }
}
