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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'label' => $this->faker->word,
            'user_id' => User::factory(),
            'phone_number' => $this->faker->phoneNumber,
            'type' => $this->faker->randomElement(["home","cell"]),
            'status' => $this->faker->randomElement(["archived","deleted"]),
            'foo_id' => $this->faker->randomDigitNotNull,
            'foo_type' => $this->faker->word,
            'tag' => $this->faker->regexify('[A-Za-z0-9]{3}'),
        ];
    }
}
