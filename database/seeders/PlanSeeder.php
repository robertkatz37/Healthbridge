<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\SubscriptionFeature;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'free', 'name' => 'Free', 'price_monthly' => 0, 'price_yearly' => 0, 'trial_days' => null,
                'stripe_price_id_monthly' => null, 'stripe_price_id_yearly' => null, 'sort_order' => 1,
                'features' => [
                    'max_locations' => '1', 'max_leads_per_month' => '5', 'featured_listing' => 'false',
                    'max_staff_accounts' => '1', 'analytics_dashboard' => 'false', 'priority_support' => 'false',
                ],
            ],
            [
                'code' => 'premium', 'name' => 'Premium', 'price_monthly' => 99, 'price_yearly' => 990, 'trial_days' => 14,
                'stripe_price_id_monthly' => 'price_premium_monthly_placeholder', 'stripe_price_id_yearly' => 'price_premium_yearly_placeholder', 'sort_order' => 2,
                'features' => [
                    'max_locations' => '2', 'max_leads_per_month' => '25', 'featured_listing' => 'false',
                    'max_staff_accounts' => '3', 'analytics_dashboard' => 'true', 'priority_support' => 'false',
                ],
            ],
            [
                'code' => 'professional', 'name' => 'Professional', 'price_monthly' => 249, 'price_yearly' => 2490, 'trial_days' => 14,
                'stripe_price_id_monthly' => 'price_professional_monthly_placeholder', 'stripe_price_id_yearly' => 'price_professional_yearly_placeholder', 'sort_order' => 3,
                'features' => [
                    'max_locations' => '5', 'max_leads_per_month' => '100', 'featured_listing' => 'true',
                    'max_staff_accounts' => '10', 'analytics_dashboard' => 'true', 'priority_support' => 'true',
                ],
            ],
            [
                'code' => 'enterprise', 'name' => 'Enterprise', 'price_monthly' => 599, 'price_yearly' => 5990, 'trial_days' => 30,
                'stripe_price_id_monthly' => 'price_enterprise_monthly_placeholder', 'stripe_price_id_yearly' => 'price_enterprise_yearly_placeholder', 'sort_order' => 4,
                'features' => [
                    'max_locations' => 'unlimited', 'max_leads_per_month' => 'unlimited', 'featured_listing' => 'true',
                    'max_staff_accounts' => 'unlimited', 'analytics_dashboard' => 'true', 'priority_support' => 'true',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);

            $plan = Plan::updateOrCreate(['code' => $planData['code']], $planData);

            foreach ($features as $featureCode => $value) {
                $feature = SubscriptionFeature::where('code', $featureCode)->first();
                if ($feature) {
                    $plan->features()->updateOrCreate(
                        ['subscription_feature_id' => $feature->id],
                        ['value' => $value]
                    );
                }
            }
        }
    }
}
