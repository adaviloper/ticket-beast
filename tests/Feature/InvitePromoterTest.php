<?php

namespace Tests\Feature;

use App\Invitation;
use App\Facades\InvitationCode;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvitePromoterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function inviting_a_promoter_via_the_cli(): void
    {
        InvitationCode::shouldReceive('generate')->andReturn(self::INVITATION_CODE);
        $this->artisan('invite-promoter', [
            'email' => self::JOHN_EMAIL,
        ]);

        self::assertEquals(1, Invitation::count());
        $invitation = Invitation::first();
        self::assertEquals(self::JOHN_EMAIL, $invitation->email);
        self::assertEquals(self::INVITATION_CODE, $invitation->code);
    }
}
