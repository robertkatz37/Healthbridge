<?php

namespace App\Enums;

enum MemoryStatus: string
{
    case None = 'none';
    case Mild = 'mild';
    case Moderate = 'moderate';
    case Severe = 'severe';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
