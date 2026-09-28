<?php

namespace App\Enums;

/**
 * Closed set of care types a Care Seeker may need. Maps conceptually 1:1 to
 * agency_categories.code (the admin-editable lookup table agencies are
 * classified against) — see DATABASE_DECISIONS.md §2 for why these are kept
 * as two separate mechanisms rather than one.
 */
enum CareType: string
{
    case IndependentLiving = 'independent_living';
    case AssistedLiving = 'assisted_living';
    case MemoryCare = 'memory_care';
    case NursingHome = 'nursing_home';
    case HomeCare = 'home_care';
    case Hospice = 'hospice';
    case Nemt = 'nemt';
    case CareHome = 'care_home';

    public function label(): string
    {
        return match ($this) {
            self::IndependentLiving => 'Independent Living',
            self::AssistedLiving => 'Assisted Living',
            self::MemoryCare => 'Memory Care',
            self::NursingHome => 'Nursing Home',
            self::HomeCare => 'Home Care',
            self::Hospice => 'Hospice',
            self::Nemt => 'Non-Emergency Medical Transportation',
            self::CareHome => 'Care Home',
        };
    }
}
