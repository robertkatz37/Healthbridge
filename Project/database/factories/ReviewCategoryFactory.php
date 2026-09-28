<?php

namespace Database\Factories;

use App\Models\ReviewCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ReviewCategory> */
class ReviewCategoryFactory extends Factory
{
    protected $model = ReviewCategory::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Care Quality', 'Staff', 'Value', 'Cleanliness', 'Activities', 'Dining']);

        return [
            'code' => Str::slug($name, '_').'_'.fake()->unique()->numberBetween(1, 999999),
            'name' => $name,
            'is_active' => true,
        ];
    }
}
