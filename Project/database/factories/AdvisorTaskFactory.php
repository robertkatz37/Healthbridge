<?php

namespace Database\Factories;

use App\Models\Advisor;
use App\Models\AdvisorTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdvisorTask> */
class AdvisorTaskFactory extends Factory
{
    protected $model = AdvisorTask::class;

    public function definition(): array
    {
        return [
            'advisor_id' => Advisor::factory(),
            'title' => fake()->sentence(3),
            'due_at' => now()->addDays(2),
        ];
    }
}
