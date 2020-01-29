<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Modifier;
use Faker\Generator as Faker;

$factory->define(Modifier::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(4),
        'name' => $faker->name,
        'content' => $faker->paragraphs(3, true),
        'total' => $faker->randomFloat(),
        'ssn' => $faker->ssn,
        'role' => $faker->randomElement(["user","admin","owner"]),
    ];
});
