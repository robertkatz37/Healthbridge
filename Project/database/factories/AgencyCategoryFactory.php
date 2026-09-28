<?php

namespace Database\Factories;

use App\Models\AgencyCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AgencyCategory> */
class AgencyCategoryFactory extends Factory
{
    protected $model = AgencyCategory::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Independent Living', 'Assisted Living', 'Memory Care', 'Nursing Home',
            'Home Care', 'Hospice', 'NEMT', 'Care Home',
        ]);

        return [
            // Faker unique() suffix guarantees uniqueness without exhausting the
            // small fixed-name pool above when many factories are created in tests.
            'code' => Str::slug($name, '_').'_'.fake()->unique()->numberBetween(1, 999999),
            'name' => $name,
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 8),
            'is_active' => true,
        ];
    }
}
