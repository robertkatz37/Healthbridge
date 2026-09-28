<?php

namespace App\Enums;

enum CommissionStatus: string
{
    case Due = 'due';
    case Invoiced = 'invoiced';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Waived = 'waived';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
