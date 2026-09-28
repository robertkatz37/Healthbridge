<?php

namespace App\Policies;

use App\Models\Advisor;
use App\Models\AdvisorTerritory;
use App\Models\User;

class AdvisorTerritoryPolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        if ($actor->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function update(User $actor, AdvisorTerritory $territory): bool
    {
        return $this->ownedByActor($actor, $territory);
    }

    public function delete(User $actor, AdvisorTerritory $territory): bool
    {
        return $this->ownedByActor($actor, $territory);
    }

    private function ownedByActor(User $actor, AdvisorTerritory $territory): bool
    {
        $advisor = Advisor::where('user_id', $actor->id)->first();

        return $advisor && $territory->advisor_id === $advisor->id;
    }
}
