<?php

namespace Tests\Feature;

use App\Invitation;
use App\User;
use Illuminate\Support\Facades\Hash;
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

    /** @test */
    public function registering_with_a_valid_invitation_code(): void
    {
        $this->withoutExceptionHandling();
        $invitation = factory(Invitation::class)->create([
            'user_id' => null,
            'code' => 'TEST_CODE_1234',
        ]);

        $response = $this->post('register', [
            'email' => self::JOHN_EMAIL,
            'password' => 'secret',
            'invitation_code' => 'TEST_CODE_1234',
        ]);

        $response->assertRedirect('backstage/concerts');
        self::assertEquals(1, User::count());

        $user = User::first();
        $this->assertAuthenticatedAs($user);
        self::assertEquals(self::JOHN_EMAIL, $user->email);
        self::assertTrue(Hash::check('secret', $user->password));
        self::assertTrue($invitation->fresh()->user->is($user));
    }
}
