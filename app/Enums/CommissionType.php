<?php

namespace App\Enums;

enum CommissionType: string
{
    case ReferralFee = 'referral_fee';
    case AdvisorCommission = 'advisor_commission';
    case AffiliateCommission = 'affiliate_commission';

    public function label(): string
    {
        return match ($this) {
            self::ReferralFee => 'Referral Fee',
            self::AdvisorCommission => 'Advisor Commission',
            self::AffiliateCommission => 'Affiliate Commission',
        };
    }
}
