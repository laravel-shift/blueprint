<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;

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
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'external_id' => fake()->word(),
            'sub_id' => Subscription::factory(),
            'expires_at' => fake()->dateTime(),
            'meta' => '[]',
            'customer_id' => Customer::factory(),
            'tran_id' => Transaction::factory(),
        ];
    }
}
