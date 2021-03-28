<?php

namespace App\Events;

use App\Concert;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ConcertAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Concert
     */
    public $concert;

    public function __construct(Concert $concert)
    {

        $this->concert = $concert;
    }
}
