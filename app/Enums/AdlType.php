<?php

namespace App\Enums;

/**
 * The standard 6 Activities of Daily Living plus medication management,
 * a common 7th category in senior care intake assessments. Stored as a
 * JSON array on care_seekers.adl_needs (multi-select), not individual
 * boolean columns — see DATABASE_DECISIONS.md.
 */
enum AdlType: string
{
    case Bathing = 'bathing';
    case Dressing = 'dressing';
    case Toileting = 'toileting';
    case Transferring = 'transferring';
    case Eating = 'eating';
    case Continence = 'continence';
    case MedicationManagement = 'medication_management';

    public function label(): string
    {
        return match ($this) {
            self::Bathing => 'Bathing',
            self::Dressing => 'Dressing',
            self::Toileting => 'Toileting',
            self::Transferring => 'Transferring',
            self::Eating => 'Eating',
            self::Continence => 'Continence',
            self::MedicationManagement => 'Medication Management',
        };
    }
}
