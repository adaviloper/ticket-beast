<?php

namespace Unit\Billing;

use App\Reservation;
use App\Ticket;
use Mockery;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    /** @test */
    public function calculating_the_total_cost()
    {
        $tickets = collect([
            (object)['price' => 1200],
            (object)['price' => 1200],
            (object)['price' => 1200],
        ]);

        $reservation = new Reservation($tickets, self::JOHN_EMAIL);

        self::assertEquals(3600, $reservation->totalCost());
    }

    /** @test */
    public function reserved_tickets_are_released_when_a_reservation_is_cancelled()
    {
        $tickets = collect([
            Mockery::spy(Ticket::class),
            Mockery::spy(Ticket::class),
            Mockery::spy(Ticket::class),
        ]);

        $reservation = new Reservation($tickets, self::JOHN_EMAIL);

        $reservation->cancel();

        foreach ($tickets as $ticket) {
            $ticket->shouldHaveReceived('release');
        }
    }

    /** @test */
    public function retrieving_the_reservations_tickets()
    {
        $tickets = collect([
            (object)['price' => 1200],
            (object)['price' => 1200],
            (object)['price' => 1200],
        ]);

        $reservation = new Reservation($tickets, self::JOHN_EMAIL);

        self::assertEquals($tickets, $reservation->tickets());
    }

    /** @test */
    public function retrieving_the_customers_email()
    {
        $reservation = new Reservation(collect(), self::JOHN_EMAIL);

        self::assertEquals(self::JOHN_EMAIL, $reservation->email());
    }
}
