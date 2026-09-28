<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'plan_id' => Plan::factory(),
            'type' => 'default',
            'stripe_id' => 'local_' . Str::uuid(),
            'stripe_status' => 'active',
        ];
    }
}
