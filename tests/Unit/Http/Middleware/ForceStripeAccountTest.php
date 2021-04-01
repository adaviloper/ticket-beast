<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\ForceStripeAccount;
use App\User;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForceStripeAccountTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function users_with_a_stripe_account_are_force_to_connect_with_stripe(): void
    {
        $this->be(factory(User::class)->create([
            'stripe_account_id' => null,
        ]));

        $middleware = new ForceStripeAccount;

        $response = new TestResponse($middleware->handle(new Request, function ($request) {
            $this->fail('Next middleware was called when it should not have been.');
        }));

        $response->assertRedirect(route('backstage.stripe-connect.connect'));
    }


}
