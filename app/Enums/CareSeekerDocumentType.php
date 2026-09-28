<?php

namespace App\Enums;

enum CareSeekerDocumentType: string
{
    case MedicalRecord = 'medical_record';
    case InsuranceCard = 'insurance_card';
    case PoaDocument = 'poa_document';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MedicalRecord => 'Medical Record',
            self::InsuranceCard => 'Insurance Card',
            self::PoaDocument => 'Power of Attorney',
            self::Other => 'Other',
        };
    }
}
