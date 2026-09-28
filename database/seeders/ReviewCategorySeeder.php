<?php

namespace Database\Seeders;

use App\Models\ReviewCategory;
use Illuminate\Database\Seeder;

class ReviewCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'care_quality', 'name' => 'Quality of Care', 'sort_order' => 1],
            ['code' => 'staff', 'name' => 'Staff', 'sort_order' => 2],
            ['code' => 'communication', 'name' => 'Communication', 'sort_order' => 3],
            ['code' => 'cleanliness', 'name' => 'Cleanliness', 'sort_order' => 4],
            ['code' => 'activities', 'name' => 'Activities', 'sort_order' => 5],
            ['code' => 'meals', 'name' => 'Meals', 'sort_order' => 6],
            ['code' => 'safety', 'name' => 'Safety', 'sort_order' => 7],
            ['code' => 'value', 'name' => 'Value', 'sort_order' => 8],
            ['code' => 'overall_satisfaction', 'name' => 'Overall Satisfaction', 'sort_order' => 9],
        ];

        foreach ($categories as $category) {
            ReviewCategory::updateOrCreate(['code' => $category['code']], $category);
        }

        // The old 'dining' code is superseded by 'meals' above — deactivate
        // rather than delete, so any review historically rated against it
        // keeps a valid, still-queryable category record.
        ReviewCategory::where('code', 'dining')->update(['is_active' => false]);
    }
}
