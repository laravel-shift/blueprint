<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
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
