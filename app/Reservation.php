<?php

namespace App;

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
