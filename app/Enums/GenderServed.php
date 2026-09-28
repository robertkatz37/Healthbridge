<?php

namespace App\Enums;

enum GenderServed: string
{
    case Any = 'any';
    case Male = 'male';
    case Female = 'female';

    public function label(): string
    {
        return match ($this) {
            self::Any => 'Any Gender',
            self::Male => 'Male Only',
            self::Female => 'Female Only',
        };
    }
}
