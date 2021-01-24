<?php

namespace Tests\Feature;

use App\User;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PromoterLoginTest extends \Tests\TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function logging_in_with_valid_credentials(): void
    {
        $this->disableExceptionHandling();
        $user = factory(User::class)->create([
            'email' => self::JANE_EMAIL,
            'password' => bcrypt('secret'),
        ]);

        $response = $this->post('/login', [
            'email' => self::JANE_EMAIL,
            'password' => 'secret',
        ]);

        $response->assertRedirect('/backstage/concerts/new');
        self::assertTrue(Auth::check());
        self::assertTrue(Auth::user()->is($user));
    }

    /** @test */
    public function logging_in_with_invalid_credentials(): void
    {
        $this->disableExceptionHandling();
        $user = factory(User::class)->create([
            'email' => self::JANE_EMAIL,
            'password' => bcrypt('secret'),
        ]);

        $response = $this->post('/login', [
            'email' => self::JANE_EMAIL,
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect('login');
        $response->assertSessionHasErrors('email');
        self::assertTrue(session()->hasOldInput('email'));
        self::assertFalse(session()->hasOldInput('password'));
        self::assertFalse(Auth::check());
    }

    /** @test */
    public function logging_in_with_an_account_that_does_not_exist(): void
    {
        $this->disableExceptionHandling();

        $response = $this->post('/login', [
            'email' => self::JANE_EMAIL,
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect('login');
        $response->assertSessionHasErrors('email');
        self::assertTrue(session()->hasOldInput('email'));
        self::assertFalse(session()->hasOldInput('password'));
        self::assertFalse(Auth::check());
    }
}
