<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Country;
use App\Models\State;

class StateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = State::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'countries_id' => Country::factory(),
            'country_code' => Country::factory()->create()->code,
            'ccid' => Country::factory(),
            'c_code' => Country::factory()->create()->code,
        ];
    }
}
