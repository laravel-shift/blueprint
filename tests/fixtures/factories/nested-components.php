<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use App\Admin\User;

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'password' => $faker->password,
    ];
});
