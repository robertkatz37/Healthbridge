<?php

namespace App\Enums;

enum AdvisorNoteType: string
{
    case Call = 'call';
    case Email = 'email';
    case Sms = 'sms';
    case Meeting = 'meeting';
    case General = 'general';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
