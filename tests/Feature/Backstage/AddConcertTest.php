<?php

namespace Tests\Feature\Backstage;

use App\Concert;
use App\User;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddConcertTest extends TestCase
{
    use DatabaseMigrations;

    private function from($url)
    {
        session()->setPreviousUrl(url($url));
        return $this;
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
                'additional_information' => "You must be 19 years of age to attend this concert.",
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

        tap(Concert::first(), function ($concert) use ($response) {
            $response->assertStatus(302);
            $response->assertRedirect("/concerts/{$concert->id}");

            self::assertEquals('No Warning', $concert->title);
            self::assertEquals('with Cruel Hand and Backtrack', $concert->subtitle);
            self::assertEquals("You must be 19 years of age to attend this concert.", $concert->additional_information);
            self::assertEquals(Carbon::parse('2017-11-18 8:00pm'), $concert->date);
            self::assertEquals('The Mosh Pit', $concert->venue);
            self::assertEquals('123 Fake St.', $concert->venue_address);
            self::assertEquals('Laraville', $concert->city);
            self::assertEquals('ON', $concert->state);
            self::assertEquals('12345', $concert->zip);
            self::assertEquals(3250, $concert->ticket_price);
            self::assertEquals(75, $concert->ticketsRemaining());
        });
    }

    /** @test */
    public function guests_cannot_add_new_concerts(): void
    {
        $response = $this->post('/backstage/concerts', [
            'title' => 'No Warning',
            'subtitle' => 'with Cruel Hand and Backtrack',
            'additional_information' => "You must be 19 years of age to attend this concert.",
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
                [
                    'title' => '',
                    'subtitle' => 'with Cruel Hand and Backtrack',
                    'additional_information' => "You must be 19 years of age to attend this concert.",
                    'date' => '2017-11-18',
                    'time' => '8:00pm',
                    'venue' => 'The Mosh Pit',
                    'venue_address' => '123 Fake St.',
                    'city' => 'Laraville',
                    'state' => 'ON',
                    'zip' => '12345',
                    'ticket_price' => '32.50',
                    'ticket_quantity' => '75',
                ]
            );

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $response->assertSessionHasErrors('title');
        self::assertEquals(0, Concert::count());
    }
}
