<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeadStatusHistory> */
class LeadStatusHistoryFactory extends Factory
{
    protected $model = LeadStatusHistory::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'from_status' => 'new',
            'to_status' => 'assigned',
        ];
    }
}
