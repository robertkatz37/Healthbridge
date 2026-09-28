<?php

namespace App\Policies;

use App\Models\User;

class CouponPolicy
{
    public function manage(User $actor): bool
    {
        return $actor->can('billing.manage_all');
    }
}
