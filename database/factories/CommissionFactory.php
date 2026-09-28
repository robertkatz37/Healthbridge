<?php

namespace Database\Factories;

use App\Enums\CommissionStatus;
use App\Models\Commission;
use App\Models\Referral;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Commission> */
class CommissionFactory extends Factory
{
    protected $model = Commission::class;

    public function definition(): array
    {
        return [
            'referral_id' => Referral::factory()->converted(),
            'amount' => fake()->randomFloat(2, 250, 3500),
            'status' => CommissionStatus::Due->value,
        ];
    }
}
