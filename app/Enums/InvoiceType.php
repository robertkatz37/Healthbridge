<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Subscription = 'subscription';
    case FeaturedListing = 'featured_listing';
    case Addon = 'addon';
    case Commission = 'commission';

    public function label(): string
    {
        return match ($this) {
            self::Subscription => 'Subscription',
            self::FeaturedListing => 'Featured Listing',
            self::Addon => 'Add-on',
            self::Commission => 'Commission',
        };
    }
}
