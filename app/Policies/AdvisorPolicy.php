<?php

namespace App\Policies;

use App\Models\Advisor;
use App\Models\User;

class AdvisorPolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        if ($actor->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function view(User $actor, Advisor $advisor): bool
    {
        return $this->isSelf($actor, $advisor)
            || $this->isManagerOf($actor, $advisor)
            || $actor->can('leads.manage_all');
    }

    public function update(User $actor, Advisor $advisor): bool
    {
        return $this->isSelf($actor, $advisor) || $actor->can('leads.manage_all');
    }

    public function viewAny(User $actor): bool
    {
        return $actor->can('advisor.manage_team') || $actor->can('leads.manage_all');
    }

    private function isSelf(User $actor, Advisor $advisor): bool
    {
        return $advisor->user_id === $actor->id;
    }

    private function isManagerOf(User $actor, Advisor $advisor): bool
    {
        $managerAdvisor = Advisor::where('user_id', $actor->id)->first();

        return $managerAdvisor && $advisor->advisor_manager_id === $managerAdvisor->id;
    }
}
