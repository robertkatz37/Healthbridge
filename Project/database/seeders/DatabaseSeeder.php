<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters: roles/permissions and lookup tables must exist before
     * any seeder that references them (e.g. AdminUserSeeder assigns a role,
     * PlanSeeder attaches SubscriptionFeature rows). DemoDataSeeder is the
     * only seeder gated to non-production environments — see guard below.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AgencyCategorySeeder::class,
            ServiceCatalogSeeder::class,
            ReviewCategorySeeder::class,
            NotificationTypeSeeder::class,
            SubscriptionFeatureSeeder::class,
            PlanSeeder::class,
            LocationSeeder::class,
            SettingSeeder::class,
            CommissionRuleSeeder::class,
            NeedsAssessmentQuestionSeeder::class,
            EmailTemplateSeeder::class,
            AdminUserSeeder::class,
        ]);

        if (! app()->environment('production')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
