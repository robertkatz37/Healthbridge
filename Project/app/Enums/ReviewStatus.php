<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case PendingModeration = 'pending_moderation';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingModeration => 'Pending Moderation',
            self::Published => 'Published',
            self::Rejected => 'Rejected',
        };
    }
}
