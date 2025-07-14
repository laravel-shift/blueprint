<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'author_id' => Author::factory(),
            'author_bio' => fake()->text(),
            'content' => fake()->paragraphs(3, true),
            'published_at' => fake()->dateTime(),
            'updated_at' => fake()->dateTime(),
            'word_count' => fake()->randomNumber(),
        ];
    }
}
