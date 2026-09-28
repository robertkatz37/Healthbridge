<?php

namespace App\Enums;

enum ReferralSource: string
{
    case MatchingEngine = 'matching_engine';
    case AdvisorManual = 'advisor_manual';
    case Affiliate = 'affiliate';
}
