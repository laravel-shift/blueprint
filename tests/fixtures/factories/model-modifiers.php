<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Modifier;

class ModifierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Modifier::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence(4),
            'name' => $this->faker->name,
            'content' => $this->faker->paragraphs(3, true),
            'amount' => $this->faker->randomFloat(3, 0, 999999.999),
            'total' => $this->faker->randomFloat(2, 0, 99999999.99),
            'overflow' => $this->faker->randomFloat(30, 0, 99999999999999999999999999999999999.999999999999999999999999999999),
            'ssn' => $this->faker->ssn,
            'role' => $this->faker->randomElement(["user","admin","owner"]),
        ];
    }
}
