<?php

namespace Tests\Feature;

use App\Invitation;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AcceptInvitationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function viewing_an_unused_invitation(): void
    {
        $this->withoutExceptionHandling();
        $invitation = factory(Invitation::class)->create([
            'user_id' => null,
            'code' => 'TEST_CODE_1234',
        ]);

        $response = $this->get('invitations/TEST_CODE_1234');

        $response->assertStatus(200);
        $response->assertViewIs('invitations.show');
        self::assertTrue($response->data('invitation')->is($invitation));
    }

    /** @test */
    public function viewing_a_used_invitation(): void
    {
        $invitation = factory(Invitation::class)->create([
            'user_id' => factory(User::class)->create(),
            'code' => 'TEST_CODE_1234',
        ]);

        $response = $this->get('invitations/TEST_CODE_1234');

        $response->assertStatus(404);
    }

    /** @test */
    public function viewing_an_invitation_that_does_not_exist(): void
    {
        $response = $this->get('invitations/TEST_CODE_1234');

        $response->assertStatus(404);
    }
}
