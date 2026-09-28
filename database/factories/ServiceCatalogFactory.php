<?php

namespace Database\Factories;

use App\Models\ServiceCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ServiceCatalog> */
class ServiceCatalogFactory extends Factory
{
    protected $model = ServiceCatalog::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'code' => Str::slug($name, '_') . '_' . fake()->unique()->numberBetween(1, 999999),
            'name' => ucwords($name),
            'is_active' => true,
        ];
    }
}
