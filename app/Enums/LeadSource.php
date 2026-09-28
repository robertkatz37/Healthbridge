<?php

namespace App\Enums;

enum LeadSource: string
{
    case NeedsAssessment = 'needs_assessment';
    case Manual = 'manual';
    case AgencyReferral = 'agency_referral';
    case WebsiteInquiry = 'website_inquiry';

    public function label(): string
    {
        return match ($this) {
            self::NeedsAssessment => 'Needs Assessment',
            self::Manual => 'Manual Entry',
            self::AgencyReferral => 'Agency Referral',
            self::WebsiteInquiry => 'Website Inquiry',
        };
    }
}
