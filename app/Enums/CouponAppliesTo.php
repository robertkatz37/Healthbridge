<?php

namespace App\Enums;

enum CouponAppliesTo: string
{
    case All = 'all';
    case Subscription = 'subscription';
    case FeaturedListing = 'featured_listing';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All Purchases',
            self::Subscription => 'Subscriptions Only',
            self::FeaturedListing => 'Featured Listing Only',
        };
    }
}
