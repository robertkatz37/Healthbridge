<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lead> */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'status' => 'new',
            'source' => 'manual',
        ];
    }
}
