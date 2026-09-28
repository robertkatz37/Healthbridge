<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function create(User $actor, Refund $refund = null): bool
    {
        return $actor->can('billing.manage_all') || $actor->can('billing.manage_own');
    }

    public function approve(User $actor): bool
    {
        return $actor->can('billing.manage_all');
    }

    public function view(User $actor, Refund $refund): bool
    {
        if ($actor->can('billing.manage_all')) {
            return true;
        }

        return $actor->can('billing.manage_own') && $actor->currentAgency()?->id === $refund->invoice->agency_id;
    }
}
