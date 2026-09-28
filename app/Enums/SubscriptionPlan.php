<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Free = 'free';
    case Premium = 'premium';
    case Professional = 'professional';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
