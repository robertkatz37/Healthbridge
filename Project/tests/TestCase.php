<?php

namespace Tests;

use Database\Seeders\AgencyCategorySeeder;
use Database\Seeders\CommissionRuleSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\NeedsAssessmentQuestionSeeder;
use Database\Seeders\NotificationTypeSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\ReviewCategorySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ServiceCatalogSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\SubscriptionFeatureSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Called after RefreshDatabase resets the DB. Seeds every seeder that
     * DatabaseSeeder treats as required platform configuration (i.e.
     * everything except AdminUserSeeder and DemoDataSeeder, which are
     * environment-specific / demo-only) — these aren't "test data", they're
     * reference config the application assumes exists (roles, plans,
     * categories, etc.), matching production seeding exactly.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, class_uses_recursive($this))) {
            $this->seed([
                RoleSeeder::class,
                AgencyCategorySeeder::class,
                ServiceCatalogSeeder::class,
                ReviewCategorySeeder::class,
                NotificationTypeSeeder::class,
                SubscriptionFeatureSeeder::class,
                PlanSeeder::class,
                SettingSeeder::class,
                CommissionRuleSeeder::class,
                NeedsAssessmentQuestionSeeder::class,
                EmailTemplateSeeder::class,
            ]);
        }
    }
}
