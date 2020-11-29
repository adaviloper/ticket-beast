<?php

namespace Tests\Unit;

use App\Concert;
use App\Order;
use App\Ticket;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function converting_to_an_array(): void
    {
        /** @var Order $order */
        $order = factory(Order::class)->create([
            'confirmation_number' => 'ORDER_CONFIRMATION_1234',
            'amount' => 6000,
            'email' => self::JANE_EMAIL,
        ]);
        $order->tickets()->saveMany(factory(Ticket::class, 5)->create());

        $result = $order->toArray();

        self::assertEquals([
            'confirmation_number' => 'ORDER_CONFIRMATION_1234',
            'email' => self::JANE_EMAIL,
            'ticket_quantity' => 5,
            'amount' => 6000,
        ], $result);
    }

    /** @test */
    public function creating_an_order_from_tickets_and_email_and_amount(): void
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
    public function retrieving_an_order_by_confirmation_number(): void
    {
        $order = factory(Order::class)->create(['confirmation_number' => 'ORDER_CONFIRMATION_1234']);

        $foundOrder = Order::findByConfirmationNumber('ORDER_CONFIRMATION_1234');

        self::assertTrue($foundOrder->is($order));
    }

    /** @test */
    public function retrieving_a_nonexistent_order_by_confirmation_number_throws_an_exception(): void
    {
        try {
            Order::findByConfirmationNumber('NONEXISTENT_CONFIRMATION_NUMBER');
        } catch (ModelNotFoundException $exception) {
            return;
        }

        self::fail('No matching order was found for the specified confirmation number, but an exception was not thrown.');
    }
}
