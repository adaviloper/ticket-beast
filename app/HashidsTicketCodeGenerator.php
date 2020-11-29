<?php

namespace App;

use Hashids\Hashids;

class HashidsTicketCodeGenerator implements TicketCodeGenerator
{
    private const MIN_LENGTH = 6;

    public function __construct(string $salt)
    {
        $this->hashids =  new Hashids($salt, self::MIN_LENGTH, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    public function generateFor(Ticket $ticket)
    {
        return $this->hashids->encode($ticket->id);
    }
}
