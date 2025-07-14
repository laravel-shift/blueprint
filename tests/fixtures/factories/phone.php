<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhoneFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'label' => fake()->word(),
            'user_id' => User::factory(),
            'phone_number' => fake()->phoneNumber(),
            'type' => fake()->randomElement(["home","cell"]),
            'status' => fake()->randomElement(["archived","deleted"]),
            'foo_id' => fake()->randomDigitNotNull(),
            'foo_type' => fake()->word(),
            'tag' => fake()->regexify('[A-Za-z0-9]{3}'),
        ];
    }
}
