<?php

namespace Tests\Feature\Backstage;

use App\User;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PromoterLoginTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function logging_in_with_valid_credentials(): void
    {
        $this->withoutExceptionHandling();
        $user = factory(User::class)->create([
            'email' => self::JANE_EMAIL,
            'password' => bcrypt('secret'),
        ]);

        $response = $this->post('/login', [
            'email' => self::JANE_EMAIL,
            'password' => 'secret',
        ]);

        $response->assertRedirect('/backstage/concerts');
        self::assertTrue(Auth::check());
        self::assertTrue(Auth::user()->is($user));
    }

    /** @test */
    public function logging_in_with_invalid_credentials(): void
    {
        $this->withoutExceptionHandling();
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
        $this->withoutExceptionHandling();

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
    function logging_out_the_current_user(): void
    {
        Auth::login(factory(User::class)->create());

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        self::assertFalse(Auth::check());
    }
}
