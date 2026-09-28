<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case PendingModeration = 'pending_moderation';
    case Published = 'published';
    case Rejected = 'rejected';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::PendingModeration => 'Pending Moderation',
            self::Published => 'Published',
            self::Rejected => 'Rejected',
            self::Hidden => 'Hidden',
        };
    }

    public function badgeColor(): array
    {
        return match ($this) {
            self::PendingModeration => ['bg' => '#FFFBEB', 'text' => '#92400E'],
            self::Published => ['bg' => 'var(--hb-emerald-100)', 'text' => 'var(--hb-emerald-700)'],
            self::Rejected, self::Hidden => ['bg' => '#FEF2F2', 'text' => '#991B1B'],
        };
    }
}
