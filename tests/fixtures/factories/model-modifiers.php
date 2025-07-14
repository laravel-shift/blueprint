<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ModifierFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'name' => fake()->name(),
            'content' => fake()->paragraphs(3, true),
            'amount' => fake()->randomFloat(3, 0, 999999.999),
            'total' => fake()->randomFloat(2, 0, 99999999.99),
            'overflow' => fake()->randomFloat(30, 0, 99999999999999999999999999999999999.999999999999999999999999999999),
            'ssn' => fake()->ssn(),
            'role' => fake()->randomElement(["user","admin","owner"]),
        ];
    }
}
