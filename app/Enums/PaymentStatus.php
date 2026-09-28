<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Pending = 'pending';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
