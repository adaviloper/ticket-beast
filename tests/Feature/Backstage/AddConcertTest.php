<?php

namespace Tests\Feature\Backstage;

use App\Concert;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AddConcertTest extends TestCase
{
    use DatabaseMigrations;

    private function validParams($overrides = [])
    {
        return array_merge([
            'title' => 'No Warning',
            'subtitle' => 'with Cruel Hand and Backtrack',
            'additional_information' => 'You must be 19 years of age to attend this concert.',
            'date' => '2017-11-18',
            'time' => '8:00pm',
            'venue' => 'The Mosh Pit',
            'venue_address' => '123 Fake St.',
            'city' => 'Laraville',
            'state' => 'ON',
            'zip' => '12345',
            'ticket_price' => '32.50',
            'ticket_quantity' => '75',
        ], $overrides);
    }

    /** @test */
    public function promoters_can_view_the_add_concert_form(): void
    {
        $user = factory(User::class)->create();

        $response =  $this->actingAs($user)->get('/backstage/concerts/new');

        $response->assertStatus(200);
    }

    /** @test */
    public function guests_cannot_view_the_add_concert_form(): void
    {
        $response =  $this->get('/backstage/concerts/new');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function adding_a_valid_concert(): void
    {
        $this->disableExceptionHandling();

        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
            ->post('/backstage/concerts', [
                'title' => 'No Warning',
                'subtitle' => 'with Cruel Hand and Backtrack',
                'additional_information' => 'You must be 19 years of age to attend this concert.',
                'date' => '2017-11-18',
                'time' => '8:00pm',
                'venue' => 'The Mosh Pit',
                'venue_address' => '123 Fake St.',
                'city' => 'Laraville',
                'state' => 'ON',
                'zip' => '12345',
                'ticket_price' => '32.50',
                'ticket_quantity' => '75',
            ]);

        tap(Concert::first(), function ($concert) use ($response, $user) {
            $response->assertRedirect('/backstage/concerts/');

            self::assertTrue($concert->user->is($user));

            self::assertFalse($concert->isPublished());

            self::assertEquals('No Warning', $concert->title);
            self::assertEquals('with Cruel Hand and Backtrack', $concert->subtitle);
            self::assertEquals('You must be 19 years of age to attend this concert.', $concert->additional_information);
            self::assertEquals(Carbon::parse('2017-11-18 8:00pm'), $concert->date);
            self::assertEquals('The Mosh Pit', $concert->venue);
            self::assertEquals('123 Fake St.', $concert->venue_address);
            self::assertEquals('Laraville', $concert->city);
            self::assertEquals('ON', $concert->state);
            self::assertEquals('12345', $concert->zip);
            self::assertEquals(3250, $concert->ticket_price);
            self::assertEquals(75, $concert->ticket_quantity);
            self::assertEquals(0, $concert->ticketsRemaining());
        });
    }

    /** @test */
    public function guests_cannot_add_new_concerts(): void
    {
        $response = $this->post('/backstage/concerts', $this->validParams());

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function title_is_required(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
            ->from('backstage/concerts/new')
            ->post(
                '/backstage/concerts',
                $this->validParams([
                    'title' => '',
                ])
            );

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $response->assertSessionHasErrors('title');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function subtitle_is_optional(): void
    {
        $this->disableExceptionHandling();

        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
            ->post('/backstage/concerts', $this->validParams([
                'subtitle' => '',
            ]));

        tap(Concert::first(), function ($concert) use ($response, $user) {
            $response->assertRedirect('/backstage/concerts');

            self::assertTrue($concert->user->is($user));
            self::assertNull($concert->subtitle);
        });
    }

    /** @test */
    public function additional_information_is_optional(): void
    {
        $this->disableExceptionHandling();

        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->post('/backstage/concerts', $this->validParams([
            'additional_information' => '',
        ]));

        tap(Concert::first(), function ($concert) use ($response, $user) {
            $response->assertRedirect('/backstage/concerts');

            self::assertTrue($concert->user->is($user));
            self::assertNull($concert->additional_information);
        });
    }

    /** @test */
    public function date_is_required(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'date' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('date');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function date_must_be_a_valid_date(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'date' => 'not a date',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('date');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function time_is_required(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'time' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('time');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function time_must_be_a_valid_time(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'time' => 'not-a-time',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('time');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function venue_is_required(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'venue' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('venue');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function venue_address_is_required(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'venue_address' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('venue_address');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function city_is_required(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'city' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('city');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function state_is_required(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'state' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('state');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function zip_is_required(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'zip' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('zip');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function ticket_price_is_required(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_price' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_price');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function ticket_price_must_be_numeric(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_price' => 'not a price',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_price');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function ticket_price_must_be_at_least_5(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_price' => '4.99',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_price');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function ticket_quantity_is_required(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_quantity' => '',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_quantity');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function ticket_quantity_must_be_numeric(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_quantity' => 'not a number',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_quantity');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function ticket_quantity_must_be_at_least_1(): void
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/backstage/concerts/new')->post('/backstage/concerts', $this->validParams([
            'ticket_quantity' => '0',
        ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('ticket_quantity');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function poster_image_is_uploaded_if_included(): void
    {
        $this->disableExceptionHandling();
        Storage::fake();
        $user = factory(User::class)->create();
        $file = File::image('concert-poster.png', 850, 1100);

        $response = $this->actingAs($user)->post('/backstage/concerts', $this->validParams([
            'poster_image' => $file,
        ]));

        tap(Concert::first(), static function ($concert) use ($file) {
            self::assertNotNull($concert->poster_image_path);
            Storage::exists($concert->poster_image_path);
            self::assertFileEquals(
                $file->getPathname(),
                Storage::path($concert->poster_image_path)
            );
        });
    }

    /** @test */
    public function poster_image_must_be_an_image(): void
    {
        Storage::fake();
        $user = factory(User::class)->create();
        $file = File::image('not-a-poster.pdf');

        $response = $this->actingAs($user)
            ->from('/backstage/concerts/new')
            ->post('/backstage/concerts', $this->validParams([
                'poster_image' => $file,
            ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('poster_image');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function poster_image_must_be_at_least_400px_wide(): void
    {
        Storage::fake();
        $user = factory(User::class)->create();
        $file = File::image('poster.png', 399, 516);

        $response = $this->actingAs($user)
            ->from('/backstage/concerts/new')
            ->post('/backstage/concerts', $this->validParams([
                'poster_image' => $file,
            ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('poster_image');
        self::assertEquals(0, Concert::count());
    }

    /** @test */
    public function poster_image_must_have_letter_aspect_ratio(): void
    {
        Storage::fake();
        $user = factory(User::class)->create();
        $file = File::image('poster.png', 851, 1100);

        $response = $this->actingAs($user)
            ->from('/backstage/concerts/new')
            ->post('/backstage/concerts', $this->validParams([
                'poster_image' => $file,
            ]));

        $response->assertRedirect('/backstage/concerts/new');
        $response->assertSessionHasErrors('poster_image');
        self::assertEquals(0, Concert::count());
    }
}
