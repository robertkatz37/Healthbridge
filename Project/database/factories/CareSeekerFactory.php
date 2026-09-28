<?php

namespace Database\Factories;

use App\Enums\CareType;
use App\Enums\MemoryStatus;
use App\Enums\MobilityLevel;
use App\Models\CareSeeker;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareSeeker>
 *
 * Produces internally coherent profiles (age, mobility, budget, care type move
 * together realistically) so Matching Engine tests aren't scored against
 * nonsensical data — see TESTING.md "Factories & Seeders".
 */
class CareSeekerFactory extends Factory
{
    protected $model = CareSeeker::class;

    public function definition(): array
    {
        $age = fake()->numberBetween(68, 95);
        $mobility = $age > 85
            ? fake()->randomElement([MobilityLevel::Wheelchair, MobilityLevel::CaneWalker, MobilityLevel::Bedbound])
            : fake()->randomElement([MobilityLevel::Independent, MobilityLevel::CaneWalker]);
        $budgetMin = fake()->numberBetween(2000, 4000);

        return [
            'family_id' => Family::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'age' => $age,
            'gender' => fake()->randomElement(['male', 'female']),
            'medical_conditions' => fake()->optional()->sentence(),
            'memory_status' => fake()->randomElement(MemoryStatus::cases())->value,
            'mobility' => $mobility->value,
            'budget_min' => $budgetMin,
            'budget_max' => $budgetMin + fake()->numberBetween(500, 3000),
            'is_veteran' => fake()->boolean(15),
            'preferred_city' => fake()->city(),
            'preferred_state' => fake()->stateAbbr(),
            'languages' => [fake()->randomElement(['English', 'Spanish', 'Mandarin', 'Vietnamese'])],
            'care_type_needed' => fake()->randomElement(CareType::cases())->value,
            'behavioral_notes' => fake()->optional()->sentence(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('(###) ###-####'),
        ];
    }
}
