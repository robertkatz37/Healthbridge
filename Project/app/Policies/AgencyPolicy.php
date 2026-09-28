<?php

namespace App\Policies;

use App\Models\Agency;
use App\Models\User;

/**
 * Governs agency listing management.
 * Owner = the user whose user_id matches the agency.
 * Staff = a user who has an AgencyStaff record for this agency.
 */
class AgencyPolicy
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
        return true; // Published agencies are public; admin can view all.
    }

    public function view(User $actor, Agency $agency): bool
    {
        if ($agency->status->value === 'published') {
            return true;
        }
        return $this->isOwnerOrStaff($actor, $agency)
            || $actor->can('agencies.manage_all');
    }

    public function create(User $actor): bool
    {
        return $actor->can('agencies.manage_own') || $actor->can('agencies.manage_all');
    }

    public function update(User $actor, Agency $agency): bool
    {
        return $this->isOwnerOrStaff($actor, $agency)
            || $actor->can('agencies.manage_all');
    }

    public function delete(User $actor, Agency $agency): bool
    {
        return $this->isOwner($actor, $agency)
            || $actor->can('agencies.manage_all');
    }

    public function moderate(User $actor, Agency $agency): bool
    {
        return $actor->can('agencies.moderate');
    }

    /**
     * Moderation queue actions (Phase 8). All gated by the same
     * agencies.moderate permission granted in Phase 6's RoleSeeder to
     * super_admin (via before()) and the moderator role — no new
     * permission was introduced since none was requested beyond what
     * already exists.
     */
    public function viewApplicationQueue(User $actor): bool
    {
        return $actor->can('agencies.moderate');
    }

    public function approve(User $actor, Agency $agency): bool
    {
        return $actor->can('agencies.moderate');
    }

    public function reject(User $actor, Agency $agency): bool
    {
        return $actor->can('agencies.moderate');
    }

    public function requestChanges(User $actor, Agency $agency): bool
    {
        return $actor->can('agencies.moderate');
    }

    public function suspend(User $actor, Agency $agency): bool
    {
        return $actor->can('agencies.moderate');
    }

    public function reactivate(User $actor, Agency $agency): bool
    {
        return $actor->can('agencies.moderate');
    }

    public function manageNotes(User $actor, Agency $agency): bool
    {
        return $actor->can('agencies.moderate');
    }

    public function viewLeads(User $actor, Agency $agency): bool
    {
        return $this->isOwnerOrStaff($actor, $agency)
            || $actor->can('agencies.manage_all');
    }

    private function isOwner(User $actor, Agency $agency): bool
    {
        return $actor->id === $agency->user_id;
    }

    private function isOwnerOrStaff(User $actor, Agency $agency): bool
    {
        if ($this->isOwner($actor, $agency)) {
            return true;
        }
        return $agency->staff()->where('user_id', $actor->id)->exists();
    }
}
