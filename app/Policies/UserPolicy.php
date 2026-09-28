<?php

namespace App\Policies;

use App\Models\User;

/**
 * Governs admin-side user management actions.
 * Super Admin bypasses all checks via before().
 */
class UserPolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        // For 'delete', bypass is NOT granted via before() — the delete() method
        // must run to enforce the self-deletion prevention rule for ALL roles,
        // including super_admin. Super admins can delete other users but not themselves.
        if ($ability === 'delete') {
            return null;
        }

        if ($actor->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    /** List all users in the admin panel. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.manage_all');
    }

    /** View a specific user's detail. */
    public function view(User $actor, User $target): bool
    {
        return $actor->can('users.manage_all');
    }

    /** Create a new user from the admin panel. */
    public function create(User $actor): bool
    {
        return $actor->can('users.manage_all');
    }

    /** Edit a user's profile or role. */
    public function update(User $actor, User $target): bool
    {
        // A user can always update their own profile through the profile routes.
        if ($actor->id === $target->id) {
            return true;
        }
        return $actor->can('users.manage_all');
    }

    /** Delete a user. */
    public function delete(User $actor, User $target): bool
    {
        // Nobody can delete themselves via admin panel; use profile/destroy for that.
        if ($actor->id === $target->id) {
            return false;
        }
        return $actor->can('users.manage_all');
    }

    /** Assign or change roles. */
    public function assignRoles(User $actor, User $target): bool
    {
        return $actor->can('roles.manage');
    }
}
