<?php

namespace Unit;

use App\Concert;
use App\Order;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class OrderTest extends \Tests\TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function converting_to_an_array()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create(['ticket_price' => 1200])->addTickets(5);
        $order = $concert->orderTickets('jane@example.com', 5);

        $result = $order->toArray();

        self::assertEquals([
            'email' => 'jane@example.com',
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
        $order = Order::forTickets($concert->findTickets(3), 'jane@example.com', 3600);

        self::assertEquals('jane@example.com', $order->email);
        self::assertEquals(3, $order->ticketQuantity());
        self::assertEquals(3600, $order->amount);
        self::assertEquals(2, $concert->ticketsRemaining());
    }
}
