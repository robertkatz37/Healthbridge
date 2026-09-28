<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyStaff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgencyStaff> */
class AgencyStaffFactory extends Factory
{
    protected $model = AgencyStaff::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'job_title' => fake()->randomElement(['Care Coordinator', 'Admissions Director', 'Office Manager']),
            'is_primary_contact' => false,
        ];
    }
}
