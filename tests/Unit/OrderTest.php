<?php

namespace Tests\Unit;

use App\Billing\Charge;
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
    public function creating_an_order_from_email_and_charge(): void
    {
        /** @var Ticket $ticket */
        $tickets = factory(Ticket::class, 3)->create();
        $charge = new Charge([
            'amount' => 3600,
            'card_last_four' => '1234',
        ]);

        /** @var Order $order */
        $order = Order::forTickets($tickets, self::JANE_EMAIL, $charge);

        self::assertEquals(self::JANE_EMAIL, $order->email);
        self::assertEquals(3, $order->ticketQuantity());
        self::assertEquals(3600, $order->amount);
        self::assertEquals('1234', $order->card_last_four);
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
