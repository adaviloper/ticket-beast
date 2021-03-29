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
            'code' => self::INVITATION_CODE,
        ]);

        $response = $this->get('invitations/' . self::INVITATION_CODE);

        $response->assertStatus(200);
        $response->assertViewIs('invitations.show');
        self::assertTrue($response->data('invitation')->is($invitation));
    }

    /** @test */
    public function viewing_a_used_invitation(): void
    {
        $invitation = factory(Invitation::class)->create([
            'user_id' => factory(User::class)->create(),
            'code' => self::INVITATION_CODE,
        ]);

        $response = $this->get('invitations/' . self::INVITATION_CODE);

        $response->assertStatus(404);
    }

    /** @test */
    public function viewing_an_invitation_that_does_not_exist(): void
    {
        $response = $this->get('invitations/' . self::INVITATION_CODE);

        $response->assertStatus(404);
    }

    /** @test */
    public function registering_with_a_valid_invitation_code(): void
    {
        $this->withoutExceptionHandling();
        $invitation = factory(Invitation::class)->create([
            'user_id' => null,
            'code' => self::INVITATION_CODE,
        ]);

        $response = $this->post('register', [
            'email' => self::JOHN_EMAIL,
            'password' => 'secret',
            'invitation_code' => self::INVITATION_CODE,
        ]);

        $response->assertRedirect('backstage/concerts');
        self::assertEquals(1, User::count());

        $user = User::first();
        $this->assertAuthenticatedAs($user);
        self::assertEquals(self::JOHN_EMAIL, $user->email);
        self::assertTrue(Hash::check('secret', $user->password));
        self::assertTrue($invitation->fresh()->user->is($user));
    }

    /** @test */
    public function registering_with_a_used_invitation(): void
    {
        $invitation = factory(Invitation::class)->create([
            'user_id' => factory(User::class)->create(),
            'code' => self::INVITATION_CODE,
        ]);
        self::assertEquals(1, User::count());

        $response = $this->post('register', [
            'email' => self::JOHN_EMAIL,
            'password' => 'secret',
            'invitation_code' => self::INVITATION_CODE,
        ]);

        $response->assertStatus(404);
        self::assertEquals(1, User::count());
    }

    /** @test */
    public function registering_with_an_invitation_code_that_does_not_exist(): void
    {
        $response = $this->post('register', [
            'email' => self::JOHN_EMAIL,
            'password' => 'secret',
            'invitation_code' => self::INVITATION_CODE,
        ]);

        $response->assertStatus(404);
        self::assertEquals(0, User::count());
    }

    /** @test */
    public function email_is_required(): void
    {
        $invitation = factory(Invitation::class)->create([
            'user_id' => null,
            'code' => self::INVITATION_CODE,
        ]);

        $response = $this->from('invitations/' . self::INVITATION_CODE)->post('register', [
            'email' => '',
            'password' => 'secret',
            'invitation_code' => self::INVITATION_CODE,
        ]);

        $response->assertRedirect('invitations/' . self::INVITATION_CODE);
        $response->assertSessionHasErrors('email');
        self::assertEquals(0, User::count());
    }

    /** @test */
    public function email_must_be_an_email(): void
    {
        $invitation = factory(Invitation::class)->create([
            'user_id' => null,
            'code' => self::INVITATION_CODE,
        ]);

        $response = $this->from('invitations/' . self::INVITATION_CODE)->post('register', [
            'email' => 'not-an-email',
            'password' => 'secret',
            'invitation_code' => self::INVITATION_CODE,
        ]);

        $response->assertRedirect('invitations/' . self::INVITATION_CODE);
        $response->assertSessionHasErrors('email');
        self::assertEquals(0, User::count());
    }

    /** @test */
    public function email_must_be_unique(): void
    {
        $existingUser = factory(User::class)->create(['email' => self::JOHN_EMAIL]);
        $invitation = factory(Invitation::class)->create([
            'user_id' => null,
            'code' => self::INVITATION_CODE,
        ]);

        $response = $this->from('invitations/' . self::INVITATION_CODE)->post('register', [
            'email' => self::JOHN_EMAIL,
            'password' => 'secret',
            'invitation_code' => self::INVITATION_CODE,
        ]);

        $response->assertRedirect('invitations/' . self::INVITATION_CODE);
        $response->assertSessionHasErrors('email');
        self::assertEquals(1, User::count());
    }

    /** @test */
    public function password_is_required(): void
    {
        $invitation = factory(Invitation::class)->create([
            'user_id' => null,
            'code' => self::INVITATION_CODE,
        ]);

        $response = $this->from('invitations/' . self::INVITATION_CODE)->post('register', [
            'email' => self::JOHN_EMAIL,
            'password' => '',
            'invitation_code' => self::INVITATION_CODE,
        ]);

        $response->assertRedirect('invitations/' . self::INVITATION_CODE);
        $response->assertSessionHasErrors('password');
        self::assertEquals(0, User::count());
    }
}
