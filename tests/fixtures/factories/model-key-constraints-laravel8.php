<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Order;
use App\Subscription;
use App\User;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'external_id' => $this->faker->word,
            'sub_id' => Subscription::factory(),
            'expires_at' => $this->faker->dateTime(),
            'meta' => '[]',
        ];
    }
}
