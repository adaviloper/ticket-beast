<?php

namespace Tests\Feature\Backstage;

use App\Concert;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestResponse;
use PHPUnit\Framework\Assert;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ViewConcertListTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp()
    {
        parent::setUp();

        Collection::macro('assertContains', function ($value) {
            Assert::assertTrue($this->contains($value), 'Failed asserting that the collection contained the specified value');
        });

        Collection::macro('assertNotContains', function ($value) {
            Assert::assertFalse($this->contains($value), 'Failed asserting that the collection did not contain the specified value');
        });
    }

    /** @test */
    public function guests_cannot_view_a_promoters_concert_list(): void
    {
        $response = $this->get('backstage/concerts');

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    /** @test */
    public function promoters_can_only_view_a_list_of_their_own_concerts(): void
    {
        $this->disableExceptionHandling();
        $user = factory(User::class)->create();
        $otherUser = factory(User::class)->create();
        $concertA = factory(Concert::class)->create(['user_id' => $user->id]);
        $concertB = factory(Concert::class)->create(['user_id' => $user->id]);
        $concertC = factory(Concert::class)->create(['user_id' => $otherUser->id]);
        $concertD = factory(Concert::class)->create(['user_id' => $user->id]);


        $response = $this->actingAs($user)->get('/backstage/concerts');

        $response->assertStatus(200);
        $response->data('concerts')->assertContains($concertA);
        $response->data('concerts')->assertContains($concertB);
        $response->data('concerts')->assertContains($concertD);
        $response->data('concerts')->assertNotContains($concertC);

    }
}
