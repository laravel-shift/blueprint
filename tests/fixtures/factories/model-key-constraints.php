<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use App\Order;

$factory->define(Order::class, function (Faker $faker) {
    return [
        'user_id' => factory(\App\User::class),
        'external_id' => $faker->word,
        'sub_id' => factory(\App\Subscription::class),
        'expires_at' => $faker->dateTime(),
        'meta' => '[]',
    ];
});
