<?php

namespace Tests\Unit\Mail;

use App\Invitation;
use App\Mail\InvitationEmail;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvitationEmailTest extends TestCase
{
    /** @test */
    public function email_contains_a_link_to_accept_the_invitation(): void
    {
        $invitation = factory(Invitation::class)->make([
            'email' => self::JOHN_EMAIL,
            'code' => self::INVITATION_CODE,
        ]);

        $email = new InvitationEmail($invitation);

        self::assertContains(url('invitations/' . self::INVITATION_CODE), $email->render());
    }

    /** @test */
    public function email_has_the_correct_subject(): void
    {
        $invitation = factory(Invitation::class)->make();

        $email = new InvitationEmail($invitation);

        self::assertContains("You're invited to join TicketBeast", $email->build()->subject);
    }
}
