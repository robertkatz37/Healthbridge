<?php

namespace Database\Seeders;

use App\Models\AgencyCategory;
use Illuminate\Database\Seeder;

class AgencyCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'independent_living', 'slug' => 'independent-living', 'name' => 'Independent Living', 'sort_order' => 1],
            ['code' => 'assisted_living', 'slug' => 'assisted-living', 'name' => 'Assisted Living', 'sort_order' => 2],
            ['code' => 'memory_care', 'slug' => 'memory-care', 'name' => 'Memory Care', 'sort_order' => 3],
            ['code' => 'nursing_home', 'slug' => 'nursing-home', 'name' => 'Nursing Home', 'sort_order' => 4],
            ['code' => 'home_care', 'slug' => 'home-care', 'name' => 'Home Care', 'sort_order' => 5],
            ['code' => 'hospice', 'slug' => 'hospice', 'name' => 'Hospice', 'sort_order' => 6],
            ['code' => 'nemt', 'slug' => 'non-emergency-medical-transportation', 'name' => 'Non-Emergency Medical Transportation', 'sort_order' => 7],
            ['code' => 'care_home', 'slug' => 'care-home', 'name' => 'Care Home', 'sort_order' => 8],
        ];

        foreach ($categories as $category) {
            AgencyCategory::updateOrCreate(['code' => $category['code']], $category);
        }
    }
}
