<?php

namespace Tests\Unit;

use App\Concert;
use App\Facades\TicketCode;
use App\Order;
use App\Ticket;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function a_ticket_can_be_release()
    {
        /** @var Concert $concert */
        $ticket = factory(Ticket::class)->states('reserved')->create();
        self::assertNotNull($ticket->reserved_at);

        $ticket->release();

        self::assertNull($ticket->fresh()->reserved_at);
    }

    /** @test */
    public function a_ticket_can_be_reserved()
    {
        /** @var Concert $concert */
        $ticket = factory(Ticket::class)->create();
        self::assertNull($ticket->reserved_at);

        $ticket->reserve();

        self::assertNotNull($ticket->fresh()->reserved_at);
    }

    /** @test */
    public function a_ticket_can_be_claimed_for_an_order(): void
    {
        $order = factory(Order::class)->create();
        $ticket = factory(Ticket::class)->create(['code' => null]);
        TicketCode::shouldReceive('generate')->andReturn('TICKETCODE1');

        $ticket->claimFor($order);

        self::assertContains($ticket->id, $order->tickets->pluck('id'));
        self::assertEquals('TICKETCODE1', $ticket->code);
    }
}
