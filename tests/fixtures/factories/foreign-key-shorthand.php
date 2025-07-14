<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'author_id' => User::factory(),
            'ccid' => Country::factory()->create()->code,
        ];
    }
}
