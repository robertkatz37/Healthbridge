<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Family;
use App\Models\Favorite;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Favorite> */
class FavoriteFactory extends Factory
{
    protected $model = Favorite::class;

    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'agency_id' => Agency::factory(),
        ];
    }
}
