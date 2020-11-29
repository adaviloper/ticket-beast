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
        $concert = factory(Concert::class)->create([
            'date' => '2017-03-12 20:00:00',
            'venue' => 'The Mosh Pit',
            'venue_address' => '123 Example Lane',
            'city' => 'Laraville',
            'state' => 'ON',
            'zip' => '17916',
        ]);
        $order = factory(Order::class)->create([
            'amount' => 8500,
            'card_last_four' => '1881',
            'confirmation_number' => 'ORDER-CONFIRMATION-1234',
            'email' => self::JOHN_EMAIL,
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
        $response->assertSee('The Red Chord');
        $response->assertSee('with Animosity and Lethargy');
        $response->assertSee('The Mosh Pit');
        $response->assertSee('123 Example Lane');
        $response->assertSee('Laraville, ON');
        $response->assertSee('17916');
        $response->assertSee(self::JOHN_EMAIL);

        $response->assertSee('2017-03-12 20:00');
        $response->assertSee('8:00pm');
    }
}
