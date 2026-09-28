<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BlogCategory> */
class BlogCategoryFactory extends Factory
{
    protected $model = BlogCategory::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Senior Living 101', 'Caregiver Resources', 'Veteran Benefits', 'Cost Guides', 'Memory Care']);
        $slug = Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999);

        return [
            'name' => $name,
            'slug' => $slug,
        ];
    }
}
