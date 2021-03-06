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

$factory->define(App\Order::class, function (Faker\Generator $faker) {
    return [
        'amount' => 5250,
        'email' => 'somebody@factory.com',
        'confirmation_number' => 'ORDER_CONFIRMATION_1234',
        'card_last_four' => '1234',
    ];
});
