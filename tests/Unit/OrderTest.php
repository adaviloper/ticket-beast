<?php

namespace Unit;

use App\Concert;
use App\Order;
use App\Reservation;
use App\Ticket;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class OrderTest extends \Tests\TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function converting_to_an_array()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create(['ticket_price' => 1200])->addTickets(5);
        $order = $concert->orderTickets(self::JANE_EMAIL, 5);

        $result = $order->toArray();

        self::assertEquals([
            'email' => self::JANE_EMAIL,
            'ticket_quantity' => 5,
            'amount' => 6000,
        ], $result);
    }

    /** @test */
    public function creating_an_order_from_tickets_and_email_and_amount()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create()->addTickets(5);

        /** @var Order $order */
        $order = Order::forTickets($concert->findTickets(3), self::JANE_EMAIL, 3600);

        self::assertEquals(self::JANE_EMAIL, $order->email);
        self::assertEquals(3, $order->ticketQuantity());
        self::assertEquals(3600, $order->amount);
        self::assertEquals(2, $concert->ticketsRemaining());
    }

    /** @test */
    public function creating_an_order_from_a_reservation()
    {
        $concert = factory(Concert::class)->create(['ticket_price' => 1200]);
        $reservation = new Reservation(
            factory(Ticket::class, 3)->create(['concert_id' => $concert->id]),
            self::JANE_EMAIL
        );

        $order = Order::fromReservation($reservation);

        self::assertEquals(self::JANE_EMAIL, $order->email);
        self::assertEquals(3, $order->ticketQuantity());
        self::assertEquals(3600, $order->amount);
    }
}
