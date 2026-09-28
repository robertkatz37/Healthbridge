<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
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
        return $actor->can('families.manage_all');
    }

    public function view(User $actor, Family $family): bool
    {
        return $actor->id === $family->user_id
            || $actor->can('families.manage_all')
            || $actor->can('advisor.manage_assigned'); // advisors can view assigned families
    }

    public function create(User $actor): bool
    {
        return $actor->hasRole('family') || $actor->can('families.manage_all');
    }

    public function update(User $actor, Family $family): bool
    {
        return $actor->id === $family->user_id || $actor->can('families.manage_all');
    }

    public function delete(User $actor, Family $family): bool
    {
        return $actor->id === $family->user_id || $actor->can('families.manage_all');
    }
}
