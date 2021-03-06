<?php

namespace App;

use App\Exceptions\NotEnoughTicketsException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @mixin \Eloquent
 *
 * @property int $id
 */
class Concert extends Model
{
    protected $guarded = [];

    protected $dates = [
        'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedDateAttribute()
    {
        return $this->date->format('F j, Y');
    }

    public function getFormattedStartTimeAttribute()
    {
        return $this->date->format('g:ia');
    }

    public function getTicketPriceInDollarsAttribute()
    {
        return number_format($this->ticket_price / 100, 2);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function isPublished()
    {
        return $this->published_at !== null;
    }

    public function publish()
    {
        $this->update(['published_at' => $this->freshTimestamp()]);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'tickets');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function hasOrdersFor($customerEmail)
    {
        return $this->orders()->where('email', $customerEmail)->exists();
    }

    public function ordersFor($customerEmail)
    {
        return $this->orders()->where('email', $customerEmail)->get();
    }

    public function createOrder(Collection $tickets, $email)
    {
        return Order::forTickets($tickets, $email, $tickets->sum('price'));
    }

    public function addTickets($quantity)
    {
        foreach (range(1, $quantity) as $i) {
            $this->tickets()->create([]);
        }

        return $this;
    }

    public function ticketsRemaining()
    {
        return $this->tickets()
            ->available()
            ->count();
    }

    public function reserveTickets($quantity, $email)
    {
        $tickets = $this->findTickets($quantity)->each(static function (Ticket $ticket) {
            $ticket->reserve();
        });

        return new Reservation($tickets, $email);
    }

    public function findTickets($ticketQuantity)
    {
        $tickets = $this->tickets()
            ->available()
            ->take($ticketQuantity)
            ->get();

        if ($tickets->count() < $ticketQuantity) {
            throw new NotEnoughTicketsException;
        }

        return $tickets;
    }
}
