<?php

namespace App\Enums;

enum ReviewReportReason: string
{
    case Spam = 'spam';
    case Offensive = 'offensive';
    case Fake = 'fake';
    case Harassment = 'harassment';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam',
            self::Offensive => 'Offensive',
            self::Fake => 'Fake',
            self::Harassment => 'Harassment',
            self::Other => 'Other',
        };
    }
}
