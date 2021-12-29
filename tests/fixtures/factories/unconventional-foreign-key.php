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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'countries_id' => Country::factory(),
            'country_code' => Country::factory()->create()->code,
            'ccid' => Country::factory()->create()->ccid,
            'c_code' => Country::factory()->create()->code,
        ];
    }
}
