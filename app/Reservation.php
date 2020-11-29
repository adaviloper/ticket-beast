<?php

namespace App;

use App\Billing\PaymentGateway;
use Illuminate\Support\Collection;

class Reservation
{
    /** @var Collection */
    private $tickets;

    /** @var string */
    private $email;

    public function __construct(Collection $tickets, $email)
    {
        $this->tickets = $tickets;
        $this->email = $email;
    }

    public function totalCost()
    {
        return $this->tickets->sum('price');
    }

    public function complete(PaymentGateway $paymentGateway, $paymentToken)
    {
        $charge = $paymentGateway->charge($this->totalCost(), $paymentToken);
        return Order::forTickets($this->tickets(), $this->email(), $charge);
    }

    public function cancel()
    {
        foreach ($this->tickets as $ticket) {
            $ticket->release();
        }
    }

    public function tickets()
    {
        return $this->tickets;
    }

    public function email()
    {
        return $this->email;
    }
}
