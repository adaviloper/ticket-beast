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

$factory->define(App\Concert::class, function (Faker\Generator $faker) {
    return [
        'title' => 'The Red Chord',
        'subtitle' => 'with Animosity and Lethargy',
        'date' => Carbon::parse('+2 weeks'),
        'ticket_price' => 2000,
        'venue' => 'The Example Theater',
        'venue_address' => '123 Example Ln',
        'city' => 'Fakevill',
        'state' => 'ON',
        'zip' => '90210',
        'additional_information' => 'For tickets, call (555) 555-5555',
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
