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

    private function validParams($overrides = []): array
    {
        return array_merge([
            'title' => 'New title',
            'subtitle' => 'New subtitle',
            'additional_information'=> 'New additional information',
            'date' => '2020-12-12',
            'time' => '8:00pm',
            'venue' => 'New venue',
            'venue_address' => 'New address',
            'city' => 'New city',
            'state' => 'New state',
            'zip' => '99999',
            'ticket_price' => '72.50',
            'ticket_quantity' => '10',
        ], $overrides);
    }

    private function oldAttributes($overrides = []): array
    {
        return array_merge([
            'title' => 'Old title',
            'subtitle' => 'Old subtitle',
            'additional_information' => 'Old additional information',
            'date' => Carbon::parse('2019-01-01 5:00pm'),
            'venue' => 'Old venue',
            'venue_address' => 'Old address',
            'city' => 'Old city',
            'state' => 'Old state',
            'zip' => '00000',
            'ticket_price' => 2000,
            'ticket_quantity' => 5,
        ], $overrides);
    }

    /** @test */
    public function promoters_can_view_the_edit_form_for_their_own_unpublished_concerts(): void
    {
        $this->disableExceptionHandling();

        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create(['user_id' => $user->id]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)->get("/backstage/concerts/{$concert->id}/edit");

        $response->assertStatus(200);
        self::assertTrue($response->data('concert')->is($concert));
    }

    /** @test */
    public function promoters_cannot_view_the_edit_form_for_their_own_published_concerts(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->states('published')->create(['user_id' => $user->id]);
        self::assertTrue($concert->isPublished());

        $response = $this->actingAs($user)->get("/backstage/concerts/{$concert->id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function promoters_cannot_view_the_edit_form_for_other_concerts(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var User $otherUser */
        $otherUser = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/backstage/concerts/{$concert->id}/edit");

        $response->assertStatus(404);
    }

    /** @test */
    public function promoters_see_a_404_when_attempting_to_view_the_edit_form_for_a_concert_that_does_not_exist(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get("/backstage/concerts/999/edit");

        $response->assertStatus(404);
    }

    /** @test */
    public function guests_are_asked_to_login_when_attempting_to_view_the_edit_form_for_any_concert(): void
    {
        /** @var User $otherUser */
        $otherUser = factory(User::class)->create();
        /** @var Concert $concert */
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
                'venue' => 'Old venue',
                'venue_address' => 'Old address',
                'city' => 'Old city',
                'state' => 'Old state',
                'zip' => '00000',
                'ticket_price' => 2000,
                'ticket_quantity' => 5,
            ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)->patch("backstage/concerts/{$concert->id}", [
            'user_id' => $user->id,
            'title' => 'New title',
            'subtitle' => 'New subtitle',
            'additional_information'=> 'New additional information',
            'date' => '2020-12-12',
            'time' => '8:00pm',
            'venue' => 'New venue',
            'venue_address' => 'New address',
            'city' => 'New city',
            'state' => 'New state',
            'zip' => '99999',
            'ticket_price' => '72.50',
            'ticket_quantity' => '10',
        ]);

        $response->assertRedirect('backstage/concerts');

        tap($concert->fresh(), static function ($concert) {
            self::assertEquals('New title', $concert->title);
            self::assertEquals('New subtitle', $concert->subtitle);
            self::assertEquals('New additional information', $concert->additional_information);
            self::assertEquals( Carbon::parse('2020-12-12 8:00pm'), $concert->date);
            self::assertEquals('New venue', $concert->venue);
            self::assertEquals('New address', $concert->venue_address);
            self::assertEquals('New city', $concert->city);
            self::assertEquals('New state', $concert->state);
            self::assertEquals( '99999', $concert->zip);
            self::assertEquals( 7250, $concert->ticket_price);
            self::assertEquals( 10, $concert->ticket_quantity);
        });
    }

    /** @test */
    public function promoters_cannot_edit_other_unpublished_concerts(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var User $otherUser */
        $otherUser = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)
            ->create($this->oldAttributes([
                'user_id' => $otherUser->id,
            ]));
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)->patch("backstage/concerts/{$concert->id}", $this->validParams());

        $response->assertStatus(404);

        self::assertArraySubset($this->oldAttributes(['user_id' => $otherUser->id]), $concert->fresh()->getAttributes());
    }

    /** @test */
    public function promoters_cannot_edit_published_concerts(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)
            ->states('published')
            ->create($this->oldAttributes([
                'user_id' => $user->id,
            ]));
        self::assertTrue($concert->isPublished());

        $response = $this->actingAs($user)->patch("backstage/concerts/{$concert->id}", $this->validParams());

        $response->assertStatus(403);
        self::assertArraySubset($this->oldAttributes(['user_id' => $user->id]), $concert->fresh()->getAttributes());
    }

    /** @test */
    public function guests_cannot_edit_concerts(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)
            ->create($this->oldAttributes([
                'user_id' => $user->id,
            ]));
        self::assertFalse($concert->isPublished());

        $response = $this->patch("backstage/concerts/{$concert->id}", $this->validParams());

        $response->assertRedirect('login');
        self::assertArraySubset($this->oldAttributes(['user_id' => $user->id]), $concert->fresh()->getAttributes());
    }

    /** @test */
    public function title_is_required(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)
            ->create([
                'user_id' => $user->id,
                'title' => 'Old title',
            ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("backstage/concerts/{$concert->id}/edit")
            ->patch(
                "/backstage/concerts/{$concert->id}",
                $this->validParams([
                    'title' => '',
                ])
            );

        $response->assertRedirect("backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('title');

        tap($concert->fresh(), static function ($concert) {
            self::assertEquals('Old title', $concert->title);
        });
    }


    /** @test */
    public function subtitle_is_optional(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'subtitle' => 'Old subtitle',
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'subtitle' => '',
            ]));

        $response->assertRedirect("/backstage/concerts");
        tap($concert->fresh(), function ($concert) {
            $this->assertNull($concert->subtitle);
        });
    }

    /** @test */
    public function additional_information_is_optional(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'additional_information' => 'Old additional information',
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'additional_information' => '',
            ]));

        $response->assertRedirect("/backstage/concerts");
        tap($concert->fresh(), function ($concert) {
            $this->assertNull($concert->additional_information);
        });
    }

    /** @test */
    public function date_is_required(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'date' => Carbon::parse('2018-01-01 8:00pm'),
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'date' => '',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('date');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals(Carbon::parse('2018-01-01 8:00pm'), $concert->date);
        });
    }

    /** @test */
    public function date_must_be_a_valid_date(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'date' => Carbon::parse('2018-01-01 8:00pm'),
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'date' => 'not a date',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('date');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals(Carbon::parse('2018-01-01 8:00pm'), $concert->date);
        });
    }

    /** @test */
    public function time_is_required(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'date' => Carbon::parse('2018-01-01 8:00pm'),
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'time' => '',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('time');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals(Carbon::parse('2018-01-01 8:00pm'), $concert->date);
        });
    }

    /** @test */
    public function time_must_be_a_valid_time(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'date' => Carbon::parse('2018-01-01 8:00pm'),
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
            'time' => 'not-a-time',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('time');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals(Carbon::parse('2018-01-01 8:00pm'), $concert->date);
        });
    }

    /** @test */
    public function venue_is_required(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'venue' => 'Old venue',
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'venue' => '',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('venue');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals('Old venue', $concert->venue);
        });
    }

    /** @test */
    public function venue_address_is_required(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'venue_address' => 'Old address',
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'venue_address' => '',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('venue_address');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals('Old address', $concert->venue_address);
        });
    }

    /** @test */
    public function city_is_required(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'city' => 'Old city',
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'city' => '',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('city');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals('Old city', $concert->city);
        });
    }

    /** @test */
    public function state_is_required(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'state' => 'Old state',
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'state' => '',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('state');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals('Old state', $concert->state);
        });
    }

    /** @test */
    public function zip_is_required(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'zip' => 'Old zip',
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'zip' => '',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('zip');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals('Old zip', $concert->zip);
        });
    }

    /** @test */
    public function ticket_price_is_required(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'ticket_price' => 5250,
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'ticket_price' => '',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('ticket_price');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals(5250, $concert->ticket_price);
        });
    }

    /** @test */
    public function ticket_price_must_be_numeric(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'ticket_price' => 5250,
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'ticket_price' => 'not a price',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('ticket_price');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals(5250, $concert->ticket_price);
        });
    }

    /** @test */
    public function ticket_price_must_be_at_least_5(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'ticket_price' => 5250,
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'ticket_price' => '4.99',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('ticket_price');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals(5250, $concert->ticket_price);
        });
    }

    /** @test */
    public function ticket_quantity_is_required(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'ticket_quantity' => 5,
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'ticket_quantity' => '',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('ticket_quantity');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals(5, $concert->ticket_quantity);
        });
    }

    /** @test */
    public function ticket_quantity_must_be_numeric(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'ticket_quantity' => 5,
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'ticket_quantity' => 'not a price',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('ticket_quantity');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals(5, $concert->ticket_quantity);
        });
    }

    /** @test */
    public function ticket_quantity_must_be_at_least_1(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->create([
            'user_id' => $user->id,
            'ticket_quantity' => 5,
        ]);
        self::assertFalse($concert->isPublished());

        $response = $this->actingAs($user)
            ->from("/backstage/concerts/{$concert->id}/edit")
            ->patch("/backstage/concerts/{$concert->id}", $this->validParams([
                'ticket_quantity' => '0',
            ]));

        $response->assertRedirect("/backstage/concerts/{$concert->id}/edit");
        $response->assertSessionHasErrors('ticket_quantity');
        tap($concert->fresh(), function ($concert) {
            $this->assertEquals(5, $concert->ticket_quantity);
        });
    }
}
