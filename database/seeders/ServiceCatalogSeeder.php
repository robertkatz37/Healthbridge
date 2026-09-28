<?php

namespace Database\Seeders;

use App\Models\AgencyCategory;
use App\Models\ServiceCatalog;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['code' => 'personal_care', 'name' => 'Personal Care', 'category' => 'assisted_living'],
            ['code' => 'medication_management', 'name' => 'Medication Management', 'category' => 'assisted_living'],
            ['code' => 'memory_programming', 'name' => 'Memory Care Programming', 'category' => 'memory_care'],
            ['code' => 'skilled_nursing', 'name' => 'Skilled Nursing', 'category' => 'nursing_home'],
            ['code' => 'housekeeping', 'name' => 'Housekeeping', 'category' => 'independent_living'],
            ['code' => 'meal_prep', 'name' => 'Meal Preparation', 'category' => 'home_care'],
            ['code' => 'transportation', 'name' => 'Transportation', 'category' => 'nemt'],
            ['code' => 'palliative_support', 'name' => 'Palliative Support', 'category' => 'hospice'],
            ['code' => 'physical_therapy', 'name' => 'Physical Therapy', 'category' => 'nursing_home'],
            ['code' => 'companion_care', 'name' => 'Companion Care', 'category' => 'home_care'],
        ];

        foreach ($services as $i => $service) {
            $category = AgencyCategory::where('code', $service['category'])->first();

            ServiceCatalog::updateOrCreate(
                ['code' => $service['code']],
                [
                    'agency_category_id' => $category?->id,
                    'name' => $service['name'],
                    'sort_order' => $i + 1,
                ]
            );
        }
    }
}
