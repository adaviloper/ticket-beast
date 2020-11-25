<?php

namespace Tests\Unit;

use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ConcertTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_get_formatted_date()
    {
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('2016-12-01 8:00pm'),
        ]);

        self::assertEquals('December 1, 2016', $concert->formattedDate);
    }

    /** @test */
    public function it_can_get_formatted_start_time()
    {
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('2016-12-01 17:00:00'),
        ]);

        self::assertEquals('5:00pm', $concert->formattedStartTime);
    }

    /** @test */
    public function it_can_get_ticket_in_dollars()
    {
        $concert = factory(Concert::class)->make([
            'ticket_price' => 6750,
        ]);

        self::assertEquals('67.50', $concert->ticketPriceInDollars);
    }

    /** @test */
    public function concerts_with_a_published_at_date_are_published()
    {
        $publishedConcertA = factory(Concert::class)->states('published')->create();
        $publishedConcertB = factory(Concert::class)->states('published')->create();
        $unpublishedConcert = factory(Concert::class)->states('unpublished')->create();

        $publishedConcerts = Concert::published()->get();

        self::assertTrue($publishedConcerts->contains($publishedConcertA));
        self::assertTrue($publishedConcerts->contains($publishedConcertB));
        self::assertFalse($publishedConcerts->contains($unpublishedConcert));
    }

    /** @test */
    public function can_order_concert_tickets()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);

        $order = $concert->orderTickets('jane@example.com', 3);

        self::assertEquals('jane@example.com', $order->email);
        self::assertEquals(3, $order->tickets()->count());
    }

    /** @test */
    public function can_add_tickets()
    {
        $concert = factory(Concert::class)->create();

        $concert->addTickets(50);

        self::assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function tickets_remaining_does_not_include_tickets_associated_with_an_order()
    {
        $concert = factory(Concert::class)->create()->addTickets(50);
        $concert->orderTickets('jane@example.com', 30);

        self::assertEquals(20, $concert->ticketsRemaining());
    }

    /** @test */
    public function trying_to_purchase_more_tickets_than_remain_throws_an_exception()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create()->addTickets(10);
        try {
            $concert->orderTickets('jane@example.com', 30);
        } catch (NotEnoughTicketsException $exception) {
            self::assertFalse($concert->hasOrdersFor('jane@example.com'));
            self::assertEquals(10, $concert->ticketsRemaining());
            return;
        }

        self::fail('Order succeeded even though there were not enough tickets remaining.');
    }

    /** @test */
    public function cannot_order_tickets_that_have_already_been_purchased()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create()->addTickets(10);
        $concert->orderTickets('jane@example.com', 8);

        try {
            $concert->orderTickets('john@example.com', 3);
        } catch (NotEnoughTicketsException $exception) {
            self::assertFalse($concert->hasOrdersFor('john@example.com'));
            self::assertEquals(2, $concert->ticketsRemaining());
            return;
        }

        self::fail('Order succeeded even though there were not enough tickets remaining.');
    }
}
