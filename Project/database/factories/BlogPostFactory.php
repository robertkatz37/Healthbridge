<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BlogPost> */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'blog_category_id' => BlogCategory::factory(),
            'author_id' => User::factory(),
            'slug' => \Illuminate\Support\Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'title' => $title,
            'excerpt' => fake()->sentence(20),
            'body' => fake()->paragraphs(6, true),
            'status' => 'published',
            'published_at' => now(),
        ];
    }
}
