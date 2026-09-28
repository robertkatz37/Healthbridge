<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Setting> */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => 'test_setting_' . fake()->unique()->numberBetween(1, 999999),
            'group' => 'general',
            'value' => fake()->word(),
            'type' => 'string',
            'is_encrypted' => false,
        ];
    }
}
