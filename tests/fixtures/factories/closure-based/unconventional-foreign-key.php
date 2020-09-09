<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\State;
use Faker\Generator as Faker;

$factory->define(State::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'countries_id' => factory(\App\Country::class),
        'country_code' => function () {
            return factory(\App\Country::class)->create()->code;
        },
        'ccid' => function () {
            return factory(\App\Country::class)->create()->ccid;
        },
        'c_code' => function () {
            return factory(\App\Country::class)->create()->code;
        },
    ];
});
