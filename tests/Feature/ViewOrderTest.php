<?php

namespace Tests\Feature;

use App\Concert;
use App\Order;
use App\Ticket;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ViewOrderTest extends \Tests\TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function user_can_view_their_order_confirmation()
    {
        $this->disableExceptionHandling();
        $concert = factory(Concert::class)->create();
        $order = factory(Order::class)->create(['confirmation_number' => 'ORDER-CONFIRMATION-1234']);
        $ticket = factory(Ticket::class)->create([
            'concert_id' => $concert->id,
            'order_id' => $order->id,
        ]);

        $response = $this->get("orders/{$order->confirmation_number}");

        $response->assertStatus(200);
    }
}
