<?php

namespace App\Enums;

enum MoveInTimeline: string
{
    case Immediately = 'immediately';
    case Within30Days = 'within_30_days';
    case Within1To3Months = 'within_1_3_months';
    case Within3To6Months = 'within_3_6_months';
    case JustResearching = 'just_researching';

    public function label(): string
    {
        return match ($this) {
            self::Immediately => 'Immediately',
            self::Within30Days => 'Within 30 days',
            self::Within1To3Months => '1–3 months',
            self::Within3To6Months => '3–6 months',
            self::JustResearching => 'Just researching',
        };
    }
}
