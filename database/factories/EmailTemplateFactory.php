<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmailTemplate> */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        return [
            'key' => 'test_template_' . fake()->unique()->numberBetween(1, 999999),
            'name' => fake()->words(3, true),
            'subject' => fake()->sentence(4),
            'body' => '<p>' . fake()->paragraph() . '</p>',
            'is_active' => true,
        ];
    }
}
