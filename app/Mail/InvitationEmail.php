<?php

namespace App\Mail;

use App\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class InvitationEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var Invitation
     */
    public $invitation;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.invitation-email')->subject("You're invited to join TicketBeast");
    }
}
