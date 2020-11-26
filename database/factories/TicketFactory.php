<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Carbon\Carbon;

$factory->define(App\Ticket::class, function (Faker\Generator $faker) {
    return [
        'concert_id' => static function () {
            return factory(\App\Concert::class)->create()->id;
        },
    ];
});

$factory->state(App\Ticket::class, 'reserved', static function (Faker\Generator $faker) {
    return [
        'reserved_at' => Carbon::now(),
    ];
});
