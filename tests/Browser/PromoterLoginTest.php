<?php

namespace Tests\Browser;

use App\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PromoterLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function logging_in_successfully(): void
    {
        $user = factory(User::class)->create([
            'email' => self::JANE_EMAIL,
            'password' => bcrypt('secret'),
        ]);

        $this->browse(static function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', self::JANE_EMAIL)
                ->type('password', 'secret')
                ->press('Log in')
                ->assertPathIs('/backstage/concerts');
        });
    }

    /** @test */
    public function logging_in_with_invalid_credentials(): void
    {
        $user = factory(User::class)->create([
            'email' => self::JANE_EMAIL,
            'password' => bcrypt('secret'),
        ]);

        $this->browse(static function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', self::JANE_EMAIL)
                ->type('password', 'wrong-password')
                ->press('Log in')
                ->assertPathIs('/login')
                ->assertInputValue('email', self::JANE_EMAIL)
                ->assertSee('credentials do not match');
        });
    }
}
