<?php

namespace App;

use App\Billing\Charge;
use App\Facades\OrderConfirmationNumber;
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
            'tickets' => $this->tickets->map(static function ($ticket) {
                return [
                    'code' => $ticket->code,
                ];
            })->all(),
        ];
    }

    public static function forTickets($tickets, $email, Charge $charge)
    {
        /** @var Order $order */
        $order = self::create([
            'confirmation_number' => OrderConfirmationNumber::generate(),
            'email' => $email,
            'amount' => $charge->amount(),
            'card_last_four' => $charge->cardLastFour(),
        ]);

        $tickets->each->claimFor($order);

        return $order;
    }

    public static function findByConfirmationNumber($confirmationNumber)
    {
        return self::where('confirmation_number', $confirmationNumber)->firstOrFail();
    }
}
