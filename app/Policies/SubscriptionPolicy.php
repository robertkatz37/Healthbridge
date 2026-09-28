<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function view(User $actor, Subscription $subscription): bool
    {
        return $actor->can('billing.manage_all') || $this->ownsAgency($actor, $subscription);
    }

    public function update(User $actor, Subscription $subscription): bool
    {
        return $actor->can('billing.manage_all') || ($actor->can('billing.manage_own') && $this->ownsAgency($actor, $subscription));
    }

    private function ownsAgency(User $actor, Subscription $subscription): bool
    {
        return $actor->currentAgency()?->id === $subscription->agency_id;
    }
}
