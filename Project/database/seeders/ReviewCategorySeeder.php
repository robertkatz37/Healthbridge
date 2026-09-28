<?php

namespace Database\Seeders;

use App\Models\ReviewCategory;
use Illuminate\Database\Seeder;

class ReviewCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'care_quality', 'name' => 'Care Quality', 'sort_order' => 1],
            ['code' => 'staff', 'name' => 'Staff', 'sort_order' => 2],
            ['code' => 'value', 'name' => 'Value', 'sort_order' => 3],
            ['code' => 'cleanliness', 'name' => 'Cleanliness', 'sort_order' => 4],
            ['code' => 'activities', 'name' => 'Activities', 'sort_order' => 5],
            ['code' => 'dining', 'name' => 'Dining', 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            ReviewCategory::updateOrCreate(['code' => $category['code']], $category);
        }
    }
}
