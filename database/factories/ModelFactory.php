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

$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Concert::class, function (Faker\Generator $faker) {
    return [
        'user_id' => static function () {
            return factory(\App\User::class)->create()->id;
        },
        'title' => 'The Red Chord',
        'subtitle' => 'with Animosity and Lethargy',
        'additional_information' => 'For tickets, call (555) 555-5555',
        'date' => Carbon::parse('+2 weeks'),
        'venue' => 'The Example Theater',
        'venue_address' => '123 Example Ln',
        'city' => 'Fakevill',
        'state' => 'ON',
        'zip' => '90210',
        'ticket_price' => 2000,
        'ticket_quantity' => 5,
    ];
});

$factory->state(App\Concert::class, 'published', static function (Faker\Generator $faker) {
    return [
        'published_at' => Carbon::parse('-1 week'),
    ];
});

$factory->state(App\Concert::class, 'unpublished', static function (Faker\Generator $faker) {
    return [
        'published_at' => null,
    ];
});

$factory->define(App\Order::class, function (Faker\Generator $faker) {
    return [
        'amount' => 5250,
        'email' => 'somebody@factory.com',
        'confirmation_number' => 'ORDER_CONFIRMATION_1234',
        'card_last_four' => '1234',
    ];
});

$factory->define(App\Invitation::class, function (Faker\Generator $faker) {
    return [

    ];
});
