<?php

namespace Database\Factories;

use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmailLog> */
class EmailLogFactory extends Factory
{
    protected $model = EmailLog::class;

    public function definition(): array
    {
        return [
            'to_address' => fake()->safeEmail(),
            'subject' => fake()->sentence(4),
            'status' => 'sent',
            'sent_at' => now(),
        ];
    }
}
