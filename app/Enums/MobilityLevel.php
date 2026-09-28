<?php

namespace App\Enums;

enum MobilityLevel: string
{
    case Independent = 'independent';
    case CaneWalker = 'cane_walker';
    case Wheelchair = 'wheelchair';
    case Bedbound = 'bedbound';

    public function label(): string
    {
        return match ($this) {
            self::Independent => 'Independent',
            self::CaneWalker => 'Cane / Walker',
            self::Wheelchair => 'Wheelchair',
            self::Bedbound => 'Bedbound',
        };
    }
}
