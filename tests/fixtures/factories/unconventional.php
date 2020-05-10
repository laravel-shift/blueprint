<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use App\Team;

$factory->define(Team::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'owner' => factory(\App\Owner::class),
        'manager' => factory(\App\User::class),
        'options' => '{}',
    ];
});
