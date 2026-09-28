<?php

namespace Database\Factories;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Agency> */
class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        $name = fake()->company().' '.fake()->randomElement(['Senior Living', 'Care Community', 'Manor', 'Gardens']);
        $minCost = fake()->numberBetween(2500, 5000);

        return [
            'user_id' => User::factory(),
            'agency_category_id' => AgencyCategory::factory(),
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraph(),
            'phone' => fake()->numerify('(###) ###-####'),
            'email' => fake()->companyEmail(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            // Deliberately NOT set here (was previously random lat/lng
            // uncorrelated with the city/state above, which produced
            // wrong distance calculations once Distance became a real,
            // functional matching factor/filter — see
            // DATABASE_DECISIONS.md). Real agency creation never sets
            // these either; leaving them null here matches production
            // and lets CityCoordinateResolver's city/state lookup
            // resolve a consistent approximate location instead. Pass
            // 'lat'/'lng' explicitly in a ->create([...]) call for tests
            // that need precise, known coordinates.
            'status' => AgencyStatus::Published->value,
            'is_featured' => fake()->boolean(20),
            'min_monthly_cost' => $minCost,
            'max_monthly_cost' => $minCost + fake()->numberBetween(500, 3000),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => AgencyStatus::Draft->value]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
