<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'price' => fake()->randomFloat(2, 0, 999999.99),
            'total' => fake()->randomFloat(2, 0, 99999999.99),
            'discount' => fake()->randomFloat(2, 0, 999999.99),
            'tax' => fake()->randomFloat(2, 0, 999999.99),
            'rate' => fake()->randomFloat(3, 0, 99.999),
            'weight' => fake()->randomFloat(0, 0, 9999999999.),
        ];
    }
}
