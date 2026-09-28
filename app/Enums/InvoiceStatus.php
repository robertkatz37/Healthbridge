<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Sent = 'sent';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Overdue = 'overdue';
    case Void = 'void';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeColor(): array
    {
        return match ($this) {
            self::Paid => ['bg' => 'var(--hb-emerald-100)', 'text' => 'var(--hb-emerald-700)'],
            self::Pending, self::Sent, self::Draft => ['bg' => '#FFFBEB', 'text' => '#92400E'],
            self::Failed, self::Overdue => ['bg' => '#FEF2F2', 'text' => '#991B1B'],
            self::Refunded => ['bg' => '#EEF2FF', 'text' => '#3730A3'],
            self::Void => ['bg' => '#F3F4F6', 'text' => '#374151'],
        };
    }
}
