<?php

namespace App;

use App\Exceptions\NotEnoughTicketsException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \Eloquent
 *
 * @property int $id
 * @property string $poster_image_path
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

    public function attendeeMessages()
    {
        return $this->hasMany(AttendeeMessage::class);
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
        $this->addTickets($this->ticket_quantity);
    }

    public function orders()
    {
        return Order::whereIn('id', $this->tickets()->pluck('order_id'));
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

    public function ticketsSold()
    {
        return $this->tickets()
            ->sold()
            ->count();
    }

    public function totalTickets()
    {
        return $this->tickets()->count();
    }

    public function percentSoldOut()
    {
        return number_format(($this->ticketsSold() / $this->totalTickets()) * 100, 2);
    }

    public function revenueInDollars()
    {
        return $this->orders()->sum('amount') / 100;
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

    public function hasPoster(): bool
    {
        return $this->poster_image_path !== null;
    }

    public function posterUrl(): string
    {
        return Storage::url($this->poster_image_path);
    }
}
