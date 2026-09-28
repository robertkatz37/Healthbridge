<?php

namespace Database\Seeders;

use App\Models\AgencyCategory;
use Illuminate\Database\Seeder;

class AgencyCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'independent_living', 'name' => 'Independent Living', 'sort_order' => 1],
            ['code' => 'assisted_living', 'name' => 'Assisted Living', 'sort_order' => 2],
            ['code' => 'memory_care', 'name' => 'Memory Care', 'sort_order' => 3],
            ['code' => 'nursing_home', 'name' => 'Nursing Home', 'sort_order' => 4],
            ['code' => 'home_care', 'name' => 'Home Care', 'sort_order' => 5],
            ['code' => 'hospice', 'name' => 'Hospice', 'sort_order' => 6],
            ['code' => 'nemt', 'name' => 'Non-Emergency Medical Transportation', 'sort_order' => 7],
            ['code' => 'care_home', 'name' => 'Care Home', 'sort_order' => 8],
        ];

        foreach ($categories as $category) {
            AgencyCategory::updateOrCreate(['code' => $category['code']], $category);
        }
    }
}
