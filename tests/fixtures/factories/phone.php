<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Phone;
use App\Models\User;

class PhoneFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Phone::class;

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
