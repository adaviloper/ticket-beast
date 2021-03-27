<?php

namespace Tests\Features\Backstage;

use App\User;
use Carbon\Carbon;
use ConcertFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OrderFactory;
use Tests\TestCase;

class ViewPublishedConcertOrdersTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function a_promoter_can_view_the_orders_of_their_own_published_concert(): void
    {
        $this->disableExceptionHandling();
        $user = factory(User::class)->create();
        $concert = ConcertFactory::createPublished(['user_id' => $user->id]);

        $order = OrderFactory::createForConcert($concert, ['created_at' => Carbon::parse('11 days ago')], 11);

        $response = $this->actingAs($user)->get("/backstage/published-concerts/{$concert->id}/orders");

        $response->assertStatus(200);
        $response->assertViewIs('backstage.published-concert-orders.index');
        self::assertTrue($response->data('concert')->is($concert));
    }

    /** @test */
    public function a_promoter_cannot_view_the_orders_of_unpublished_concerts(): void
    {
        $user = factory(User::class)->create();
        $concert = ConcertFactory::createUnpublished(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/backstage/published-concerts/{$concert->id}/orders");

        $response->assertStatus(404);
    }

    /** @test */
    public function a_promoter_cannot_view_the_orders_of_another_published_concert(): void
    {
        $user = factory(User::class)->create();
        $otherUser = factory(User::class)->create();
        $concert = ConcertFactory::createPublished(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/backstage/published-concerts/{$concert->id}/orders");

        $response->assertStatus(404);
    }

    /** @test */
    public function a_guest_cannot_view_the_orders_of_any_published_concert(): void
    {
        $concert = ConcertFactory::createPublished();

        $response = $this->get("/backstage/published-concerts/{$concert->id}/orders");

        $response->assertRedirect('/login');
    }
}
