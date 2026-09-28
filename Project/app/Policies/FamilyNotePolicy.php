<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\FamilyNote;
use App\Models\User;

class FamilyNotePolicy
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
        return $actor->hasRole('family');
    }

    public function view(User $actor, FamilyNote $note): bool
    {
        return $this->ownedByActor($actor, $note);
    }

    public function create(User $actor): bool
    {
        return $actor->hasRole('family');
    }

    public function update(User $actor, FamilyNote $note): bool
    {
        return $this->ownedByActor($actor, $note);
    }

    public function delete(User $actor, FamilyNote $note): bool
    {
        return $this->ownedByActor($actor, $note);
    }

    private function ownedByActor(User $actor, FamilyNote $note): bool
    {
        return Family::where('user_id', $actor->id)->where('id', $note->family_id)->exists();
    }
}
