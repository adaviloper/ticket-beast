<?php

namespace Tests\Unit;

use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use App\Order;
use App\Ticket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
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
    public function concerts_can_be_published(): void
    {
        $concert = factory(Concert::class)->create(['published_at' => null]);
        self::assertFalse($concert->isPublished());

        $concert->publish();

        self::assertTrue($concert->isPublished());
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
        $concert = factory(Concert::class)->create();
        $concert->tickets()->saveMany(factory(Ticket::class, 30)->create(['order_id' => 1]));
        $concert->tickets()->saveMany(factory(Ticket::class, 20)->create(['order_id' => null]));

        self::assertEquals(20, $concert->ticketsRemaining());
    }

    /** @test */
    public function trying_to_reserve_more_tickets_than_remain_throws_an_exception()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create()->addTickets(10);
        try {
            $concert->reserveTickets(30, self::JANE_EMAIL);
        } catch (NotEnoughTicketsException $exception) {
            self::assertFalse($concert->hasOrdersFor(self::JANE_EMAIL));
            self::assertEquals(10, $concert->ticketsRemaining());
            return;
        }

        self::fail('Order succeeded even though there were not enough tickets remaining.');
    }

    /** @test */
    public function can_reserve_available_tickets()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create()->addTickets(3);
        self::assertEquals(3, $concert->ticketsRemaining());

        $reservation = $concert->reserveTickets(2, 'john@example.com');

        self::assertCount(2, $reservation->tickets());
        self::assertEquals(1, $concert->ticketsRemaining());
    }

    /** @test */
    public function cannot_reserve_tickets_that_have_already_been_purchased()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create()->addTickets(3);
        $order = factory(Order::class)->create();
        $order->tickets()->saveMany($concert->tickets->take(2));

        try {
            $concert->reserveTickets(2, self::JANE_EMAIL);
        } catch (NotEnoughTicketsException $exception) {
            self::assertEquals(1, $concert->ticketsRemaining());
            return;
        }

        self::fail('Reserving tickets succeeded even though the tickets were already sold.');
    }

    /** @test */
    public function cannot_reserve_tickets_that_have_already_been_reserved()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create()->addTickets(3);
        $concert->reserveTickets(2, self::JOHN_EMAIL);

        try {
            $concert->reserveTickets(2, self::JOHN_EMAIL);
        } catch (NotEnoughTicketsException $exception) {
            self::assertEquals(1, $concert->ticketsRemaining());
            return;
        }

        self::fail('Reserving tickets succeeded even though the tickets were already reserved.');
    }
}
