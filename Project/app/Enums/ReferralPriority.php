<?php

namespace App\Enums;

/**
 * Mirrors TaskPriority (Phase 11 completion pass) exactly.
 */
enum ReferralPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => '#6B7280',
            self::Medium => '#D97706',
            self::High => '#DC2626',
        };
    }
}
