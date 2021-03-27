<?php

namespace Tests\Feature\Backstage;

use App\Concert;
use App\User;
use ConcertFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PublishConcertTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function a_promoter_can_publish_their_own_concert(): void
    {
        $this->disableExceptionHandling();
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->states('unpublished')->create([
            'user_id' => $user->id,
            'ticket_quantity' => 3,
        ]);

        $response = $this->actingAs($user)->post('/backstage/published-concerts', [
            'concert_id' => $concert->id,
        ]);

        $response->assertRedirect('/backstage/concerts');
        tap($concert->fresh(), static function ($concert) {
            self::assertTrue($concert->fresh()->isPublished());
            self::assertEquals(3, $concert->fresh()->ticketsRemaining());
        });
    }

    /** @test */
    public function a_concert_can_only_be_published_once(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $concert = ConcertFactory::createPublished([
            'user_id' => $user->id,
            'ticket_quantity' => 3,
        ]);
        self::assertEquals(3, $concert->fresh()->ticketsRemaining());

        $response = $this->actingAs($user)->post('/backstage/published-concerts', [
            'concert_id' => $concert->id,
        ]);

        $response->assertStatus(422);
        self::assertEquals(3, $concert->fresh()->ticketsRemaining());
    }

    /** @test */
    public function a_promoter_cannot_publish_other_concerts(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var User $otherUser */
        $otherUser = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->states('unpublished')->create([
            'user_id' => $otherUser->id,
            'ticket_quantity' => 3,
        ]);

        $response = $this->actingAs($user)->post('/backstage/published-concerts', [
            'concert_id' => $concert->id,
        ]);

        $response->assertStatus(404);
        $concert = $concert->fresh();
        self::assertFalse($concert->isPublished());
        self::assertEquals(0, $concert->ticketsRemaining());
    }

    /** @test */
    public function a_guest_cannot_publish_concerts(): void
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->states('unpublished')->create([
            'ticket_quantity' => 3,
        ]);

        $response = $this->post('/backstage/published-concerts', [
            'concert_id' => $concert->id,
        ]);

        $response->assertRedirect('/login');
        $concert = $concert->fresh();
        self::assertFalse($concert->isPublished());
        self::assertEquals(0, $concert->ticketsRemaining());
    }

    /** @test */
    public function concerts_that_do_not_exist_cannot_be_published(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->post('/backstage/published-concerts', [
            'concert_id' => 999,
        ]);

        $response->assertStatus(404);
    }
}
