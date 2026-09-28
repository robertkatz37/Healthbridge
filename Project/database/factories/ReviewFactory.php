<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Agency;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Review> */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $rating = fake()->numberBetween(3, 5);

        return [
            'agency_id' => Agency::factory(),
            'reviewer_name' => fake()->firstName().' '.fake()->randomLetter().'.',
            'reviewer_relationship' => fake()->randomElement(['resident', 'family_member', 'former_resident']),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'overall_rating' => $rating,
            'is_verified' => fake()->boolean(60),
            'status' => ReviewStatus::Published->value,
            'published_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    public function pendingModeration(): static
    {
        return $this->state(fn () => [
            'status' => ReviewStatus::PendingModeration->value,
            'published_at' => null,
        ]);
    }
}
