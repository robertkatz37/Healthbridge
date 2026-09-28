<?php

namespace App\Policies;

use App\Models\Advisor;
use App\Models\Family;
use App\Models\TourRequest;
use App\Models\User;

/**
 * Extended in Phase 13 to cover all three parties who can legitimately
 * see/act on a tour — Advisor (owns/manages the Lead), Family (it's
 * their Care Seeker's tour), and Agency (it's their tour to run) — "must
 * all see the same tour information appropriate to their permissions."
 */
class TourRequestPolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        if ($actor->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function view(User $actor, TourRequest $tourRequest): bool
    {
        return $this->advisorOwnedOrManaged($actor, $tourRequest)
            || $this->familyOwned($actor, $tourRequest)
            || $this->agencyOwned($actor, $tourRequest)
            || $actor->can('leads.manage_all');
    }

    public function update(User $actor, TourRequest $tourRequest): bool
    {
        return $this->advisorOwnedOrManaged($actor, $tourRequest)
            || $this->agencyOwned($actor, $tourRequest)
            || $actor->can('leads.manage_all');
    }

    /**
     * Deliberately narrower than update() — a family can cancel their
     * own tour ("Cancel tour requests" is explicitly their one allowed
     * action) but must not be able to reschedule/confirm/complete,
     * which stay advisor/agency-only via update().
     */
    public function cancel(User $actor, TourRequest $tourRequest): bool
    {
        return $this->familyOwned($actor, $tourRequest) || $this->update($actor, $tourRequest);
    }

    public function delete(User $actor, TourRequest $tourRequest): bool
    {
        return $this->advisorOwnedOrManaged($actor, $tourRequest) || $actor->can('leads.manage_all');
    }

    private function advisorOwnedOrManaged(User $actor, TourRequest $tourRequest): bool
    {
        $lead = $tourRequest->lead;
        if (!$lead || !$lead->advisor_id) {
            return false;
        }

        $advisor = Advisor::where('user_id', $actor->id)->first();
        if (!$advisor) {
            return false;
        }

        if ($lead->advisor_id === $advisor->id) {
            return true;
        }

        return Advisor::where('id', $lead->advisor_id)
            ->where('advisor_manager_id', $advisor->id)
            ->exists();
    }

    private function familyOwned(User $actor, TourRequest $tourRequest): bool
    {
        return Family::where('user_id', $actor->id)->where('id', $tourRequest->family_id)->exists();
    }

    private function agencyOwned(User $actor, TourRequest $tourRequest): bool
    {
        return $actor->id === $tourRequest->agency?->user_id;
    }
}
