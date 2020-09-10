<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Admin\User;
use Faker\Generator as Faker;

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'password' => $faker->password,
    ];
});
