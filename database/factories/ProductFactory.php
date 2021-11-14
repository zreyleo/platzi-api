<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Product;
use Faker\Generator as Faker;

$factory->define(Product::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'price' => $faker->numberBetween(10, 90),
        'category_id' => function () {
            return \App\Category::query()->inRandomOrder()->first()->id;
        },
        'created_by' => function () {
            return \App\User::query()->inRandomOrder()->first()->id;
        }
    ];
});
