<?php

namespace Tests\Feature\Backstage;

use App\User;
use App\Concert;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class EditConcertTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function promoters_can_view_the_edit_form_for_their_own_unpublished_concerts(): void
    {
        $this->disableExceptionHandling();

        $user = factory(User::class)->create();
        $concert = factory(Concert::class)->create(['user_id' => $user->id]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)->get("/backstage/concerts/{$concert->id}/edit");

        $response->assertStatus(200);
        self::assertTrue($response->data('concert')->is($concert));
    }

    /** @test */
    public function promoters_cannot_view_the_edit_form_for_their_own_published_concerts(): void
    {
        $user = factory(User::class)->create();
        $concert = factory(Concert::class)->states('published')->create(['user_id' => $user->id]);
        self::assertTrue($concert->isPublished());

        $response = $this->actingAs($user)->get("/backstage/concerts/{$concert->id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function promoters_cannot_view_the_edit_form_for_other_concerts(): void
    {
        $user = factory(User::class)->create();
        $otherUser = factory(User::class)->create();
        $concert = factory(Concert::class)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/backstage/concerts/{$concert->id}/edit");

        $response->assertStatus(404);
    }

    /** @test */
    public function promoters_see_a_404_when_attempting_to_view_the_edit_form_for_a_concert_that_does_not_exist(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get("/backstage/concerts/999/edit");

        $response->assertStatus(404);
    }

    /** @test */
    public function guests_are_asked_to_login_when_attempting_to_view_the_edit_form_for_any_concert(): void
    {
        $otherUser = factory(User::class)->create();
        $concert = factory(Concert::class)->create(['user_id' => $otherUser->id]);

        $response = $this->get("/backstage/concerts/{$concert->id}/edit");

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function guests_are_asked_to_login_when_attempting_to_view_the_edit_form_for_a_concert_that_does_not_exist(): void
    {
        $response = $this->get("/backstage/concerts/999/edit");

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function promoters_can_edit_their_own_unpublished_concerts(): void
    {
        $this->disableExceptionHandling();

        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)
            ->create([
                'user_id' => $user->id,
                'title' => 'Old title',
                'subtitle' => 'Old subtitle',
                'additional_information' => 'Old additional information',
                'date' => Carbon::parse('2019-01-01 5:00pm'),
                'ticket_price' => 2000,
                'venue' => 'Old venue',
                'venue_address' => 'Old address',
                'city' => 'Old city',
                'state' => 'Old state',
                'zip' => '00000',
            ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)->patch("backstage/concerts/{$concert->id}", [
            'user_id' => $user->id,
            'title' => 'New title',
            'subtitle' => 'New subtitle',
            'additional_information'=> 'New additional information',
            'date' => '2020-12-12',
            'time' => '8:00pm',
            'ticket_price' => '72.50',
            'venue' => 'New venue',
            'venue_address' => 'New address',
            'city' => 'New city',
            'state' => 'New state',
            'zip' => '99999',
        ]);

        $response->assertRedirect('backstage/concerts');

        $freshConcert = tap($concert->fresh(), static function ($concert) {
            self::assertEquals('New title', $concert->title);
            self::assertEquals('New subtitle', $concert->subtitle);
            self::assertEquals('New additional information', $concert->additional_information);
            self::assertEquals( Carbon::parse('2020-12-12 8:00pm'), $concert->date);
            self::assertEquals( 7250, $concert->ticket_price);
            self::assertEquals('New venue', $concert->venue);
            self::assertEquals('New address', $concert->venue_address);
            self::assertEquals('New city', $concert->city);
            self::assertEquals('New state', $concert->state);
            self::assertEquals( '99999', $concert->zip);
        });
    }
}
