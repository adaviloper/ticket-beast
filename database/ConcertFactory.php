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

use App\Concert;
use Carbon\Carbon;

class ConcertFactory
{
    public static function createPublished($overrides = []): Concert
    {
        return tap(factory(Concert::class)->create($overrides), static function ($concert) {
            $concert->publish();
        });
    }

    public static function createUnpublished($overrides = [])
    {
        return factory(Concert::class)->states('unpublished')->create($overrides);
    }
}
