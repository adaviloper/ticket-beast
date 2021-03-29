<?php

namespace Tests\Feature;

use App\Invitation;
use App\Facades\InvitationCode;
use App\Mail\InvitationEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvitePromoterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function inviting_a_promoter_via_the_cli(): void
    {
        Mail::fake();
        InvitationCode::shouldReceive('generate')->andReturn(self::INVITATION_CODE);
        $this->artisan('invite-promoter', [
            'email' => self::JOHN_EMAIL,
        ]);

        self::assertEquals(1, Invitation::count());
        $invitation = Invitation::first();
        self::assertEquals(self::JOHN_EMAIL, $invitation->email);
        self::assertEquals(self::INVITATION_CODE, $invitation->code);

        Mail::assertSent(InvitationEmail::class, static function (InvitationEmail $mail) use ($invitation) {
            return $mail->hasTo(self::JOHN_EMAIL) && $mail->invitation->is($invitation);
        });
    }
}
