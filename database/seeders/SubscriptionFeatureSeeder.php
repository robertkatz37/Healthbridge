<?php

namespace Database\Seeders;

use App\Models\SubscriptionFeature;
use Illuminate\Database\Seeder;

class SubscriptionFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['code' => 'max_locations', 'name' => 'Maximum Locations', 'value_type' => 'integer'],
            ['code' => 'max_leads_per_month', 'name' => 'Maximum Leads per Month', 'value_type' => 'integer'],
            ['code' => 'featured_listing', 'name' => 'Featured Listing Eligibility', 'value_type' => 'boolean'],
            ['code' => 'max_staff_accounts', 'name' => 'Maximum Staff Accounts', 'value_type' => 'integer'],
            ['code' => 'analytics_dashboard', 'name' => 'Analytics Dashboard', 'value_type' => 'boolean'],
            ['code' => 'priority_support', 'name' => 'Priority Support', 'value_type' => 'boolean'],
        ];

        foreach ($features as $feature) {
            SubscriptionFeature::updateOrCreate(['code' => $feature['code']], $feature);
        }
    }
}
