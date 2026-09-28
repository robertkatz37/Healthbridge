<?php

namespace App\Enums;

enum DocumentType: string
{
    case License = 'license';
    case Insurance = 'insurance';
    case Accreditation = 'accreditation';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
