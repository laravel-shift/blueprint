<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Modifier;
use Faker\Generator as Faker;

$factory->define(Modifier::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(4),
        'name' => $faker->name,
        'content' => $faker->paragraphs(3, true),
        'amount' => $faker->randomFloat(3, 0, 999999.999),
        'total' => $faker->randomFloat(2, 0, 99999999.99),
        'overflow' => $faker->randomFloat(30, 0, 99999999999999999999999999999999999.999999999999999999999999999999),
        'ssn' => $faker->ssn,
        'role' => $faker->randomElement(["user","admin","owner"]),
    ];
});
