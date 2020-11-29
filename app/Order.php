<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Eloquent
 */
class Order extends Model
{
    protected $guarded = [];

    public function concert()
    {
        return $this->belongsTo(Concert::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketQuantity()
    {
        return $this->tickets()->count();
    }

    public function toArray()
    {
        return [
            'amount' => $this->amount,
            'confirmation_number' => $this->confirmation_number,
            'email' => $this->email,
            'ticket_quantity' => $this->ticketQuantity(),
        ];
    }

    public static function forTickets($tickets, $email, $amount)
    {
        /** @var Order $order */
        $order = self::create([
            'confirmation_number' => app(OrderConfirmationNumberGenerator::class)->generate(),
            'email' => $email,
            'amount' => $amount,
        ]);

        $order->tickets()->saveMany($tickets);

        return $order;
    }

    public static function findByConfirmationNumber($confirmationNumber)
    {
        return self::where('confirmation_number', $confirmationNumber)->firstOrFail();
    }
}
