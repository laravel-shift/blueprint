<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\State;
use Faker\Generator as Faker;

$factory->define(State::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'country_code' => function () {
            return factory(\App\Country::class)->create()->code;
        },
    ];
});
