<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(Model::class, function (Faker $faker) {
    return [
        'market_type' => $faker->randomNumber(),
        'deposit' => $faker->randomNumber(),
    ];
});
