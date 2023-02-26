<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Author;
use App\Models\Post;

class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'author_id' => Author::factory(),
            'author_bio' => $this->faker->text,
            'content' => $this->faker->paragraphs(3, true),
            'published_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
            'word_count' => $this->faker->randomNumber(),
        ];
    }
}
