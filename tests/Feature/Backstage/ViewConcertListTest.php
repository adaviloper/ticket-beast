<?php

namespace Tests\Feature\Backstage;

use App\Concert;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ViewConcertListTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function guests_cannot_view_a_promoters_concert_list(): void
    {
        $response = $this->get('backstage/concerts');

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    /** @test */
    public function promoters_can_view_a_list_of_their_concerts(): void
    {
        $this->disableExceptionHandling();
        $user = factory(User::class)->create();
        $concerts = factory(Concert::class, 3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/backstage/concerts');

        $response->assertStatus(200);
        self::assertTrue($response->original->getData()['concerts']->contains($concerts[0]));
        self::assertTrue($response->original->getData()['concerts']->contains($concerts[1]));
        self::assertTrue($response->original->getData()['concerts']->contains($concerts[2]));

    }
}
