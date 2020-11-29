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
    public function user_can_view_their_order_confirmation(): void
    {
        $this->disableExceptionHandling();
        $concert = factory(Concert::class)->create();
        $order = factory(Order::class)->create([
            'amount' => 8500,
            'card_last_four' => '1881',
            'confirmation_number' => 'ORDER-CONFIRMATION-1234',
        ]);
        $ticketA = factory(Ticket::class)->create([
            'code' => 'TICKET-CODE-123',
            'concert_id' => $concert->id,
            'order_id' => $order->id,
        ]);
        $ticketB = factory(Ticket::class)->create([
            'code' => 'TICKET-CODE-456',
            'concert_id' => $concert->id,
            'order_id' => $order->id,
        ]);

        $response = $this->get("orders/{$order->confirmation_number}");

        $response->assertStatus(200);
        $response->assertViewHas('order', static function ($viewOrder) use ($order) {
            return $order->id === $viewOrder->id;
        });
        $response->assertSee($order->confirmation_number);
        $response->assertSee('$85.00');
        $response->assertSee('**** **** **** 1881');
        $response->assertSee('TICKET-CODE-123');
        $response->assertSee('TICKET-CODE-456');
    }
}
