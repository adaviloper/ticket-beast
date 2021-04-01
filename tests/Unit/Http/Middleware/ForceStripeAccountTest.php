<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\ForceStripeAccount;
use App\User;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

    /** @test */
    public function user_with_a_stripe_can_continue(): void
    {
        $this->be(factory(User::class)->create([
            'stripe_account_id' => 'test_stripe_account_1234',
        ]));

        $request = new Request;
        $next = new class {
            public $called = false;
            public function __invoke($request)
            {
                $this->called = true;
                return $request;
            }
        };
        $middleware = new ForceStripeAccount;

        $response = $middleware->handle($request, $next);

        self::assertTrue($next->called);
        self::assertSame($request, $response);
    }

    /** @test */
    public function middleware_is_applied_to_all_backstage_routes(): void
    {
        $routes = [
            'backstage.concerts.index',
            'backstage.concerts.new',
            'backstage.concerts.store',
            'backstage.concerts.edit',
            'backstage.concerts.update',
            'backstage.published-concerts.store',
            'backstage.published-concert-orders.index',
            'backstage.concert-messages.new',
            'backstage.concert-messages.store',
        ];

        foreach ($routes as $route) {
            self::assertContains(
                ForceStripeAccount::class,
                Route::getRoutes()->getByName($route)->gatherMiddleware()
            );
        }
    }
}
