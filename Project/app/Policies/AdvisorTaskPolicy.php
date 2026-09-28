<?php

namespace App\Policies;

use App\Models\Advisor;
use App\Models\AdvisorTask;
use App\Models\User;

class AdvisorTaskPolicy
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

    public function view(User $actor, AdvisorTask $task): bool
    {
        return $this->ownedOrManaged($actor, $task) || $actor->can('leads.manage_all');
    }

    public function update(User $actor, AdvisorTask $task): bool
    {
        return $this->ownedOrManaged($actor, $task) || $actor->can('leads.manage_all');
    }

    public function delete(User $actor, AdvisorTask $task): bool
    {
        return $this->ownedOrManaged($actor, $task) || $actor->can('leads.manage_all');
    }

    public function create(User $actor): bool
    {
        return $actor->can('advisor.manage_assigned');
    }

    private function ownedOrManaged(User $actor, AdvisorTask $task): bool
    {
        $advisor = Advisor::where('user_id', $actor->id)->first();
        if (!$advisor) {
            return false;
        }

        if ($task->advisor_id === $advisor->id) {
            return true;
        }

        // Advisor Manager overseeing their team's task
        return Advisor::where('id', $task->advisor_id)
            ->where('advisor_manager_id', $advisor->id)
            ->exists();
    }
}
