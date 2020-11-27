<?php

namespace Unit\Billing;

use App\Billing\FakePaymentGateway;
use App\Concert;
use App\Reservation;
use App\Ticket;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use DatabaseMigrations;

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

    /** @test */
    public function completing_a_reservation()
    {
        $concert = factory(Concert::class)->create(['ticket_price' => 1200]);
        $reservation = new Reservation(
            factory(Ticket::class, 3)->create(['concert_id' => $concert->id]),
            self::JOHN_EMAIL
        );
        $paymentGateway = new FakePaymentGateway();

        $order = $reservation->complete($paymentGateway, $paymentGateway->getValidTestToken());

        self::assertEquals(self::JOHN_EMAIL, $order->email);
        self::assertEquals(3, $order->ticketQuantity());
        self::assertEquals(3600, $order->amount);
        self::assertEquals(3600, $paymentGateway->totalCharges());
    }
}
