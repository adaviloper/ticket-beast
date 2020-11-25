<?php

namespace Unit;

use App\Concert;
use App\Order;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class OrderTest extends \Tests\TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function tickets_are_release_when_an_order_is_cancelled()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create();
        $concert->addTickets(10);
        $order = $concert->orderTickets('jane@example.com', 5);
        $this->assertEquals(5, $concert->ticketsRemaining());

        $order->cancel();

        $this->assertEquals(10, $concert->ticketsRemaining(), '');
        $this->assertNull(Order::find($order->id));
    }
}
