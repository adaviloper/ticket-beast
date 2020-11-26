<?php

namespace Unit;

use App\Concert;
use App\Order;
use App\Ticket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TicketTest extends \Tests\TestCase
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
}
