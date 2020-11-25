<?php

namespace Unit;

use App\Concert;
use App\Order;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TicketTest extends \Tests\TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function a_ticket_can_be_release()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create();
        $concert->addTickets(1);
        $order = $concert->orderTickets('jane@example.com', 1);
        $ticket = $order->tickets()->first();
        $this->assertEquals($order->id, $ticket->order_id);

        $ticket->release();

        $this->assertNull($ticket->fresh()->order_id);
    }
}
