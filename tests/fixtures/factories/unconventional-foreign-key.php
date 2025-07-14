<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class StateFactory extends Factory
{
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
