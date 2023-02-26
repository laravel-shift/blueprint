<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Model;

class ModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Model::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'market_type' => $this->faker->numberBetween(-8, 8),
            'deposit' => $this->faker->randomNumber(),
        ];
    }
}
