<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ModelFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'market_type' => fake()->numberBetween(-8, 8),
            'deposit' => fake()->randomNumber(),
        ];
    }
}
