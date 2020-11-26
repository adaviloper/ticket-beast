<?php

namespace Tests\Feature;

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;
use App\Concert;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @var FakePaymentGateway
     */
    protected $paymentGateway;

    protected $response;

    public function setUp()
    {
        parent::setUp();

        $this->paymentGateway = new FakePaymentGateway();
        $this->app->instance(PaymentGateway::class, $this->paymentGateway);
    }

    public function orderTickets($concert, $params)
    {
        return $this->postJson("concerts/{$concert->id}/orders", $params);
    }

    public function assertValidationError($field)
    {
        $this->response->assertStatus(422);
        self::assertArrayHasKey($field, $this->response->decodeResponseJson());
    }

    /** @test */
    public function customer_can_purchase_tickets_to_a_published_concert()
    {
        $this->disableExceptionHandling();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->states('published')->create([
            'ticket_price' => 3250,
        ])->addTickets(3);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(201);

        $response->assertJson([
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'amount' => 9750,
        ]);

        self::assertEquals(9750, $this->paymentGateway->totalCharges());
        self::assertTrue($concert->hasOrdersFor('john@example.com'));
        self::assertEquals(3, $concert->ordersFor('john@example.com')->first()->ticketQuantity());
    }

    /** @test */
    public function email_is_required_to_purchase_tickets()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->response = $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('email');
    }

    /** @test */
    public function email_must_be_valid_to_purchase_tickets()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->response = $this->orderTickets($concert, [
            'email' => 'not-an-email-address',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('email');
    }

    /** @test */
    public function ticket_quantity_is_required_to_purchase_tickets()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->response = $this->orderTickets($concert,[
            'email' => 'john@example.com',
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('ticket_quantity');
    }

    /** @test */
    public function ticket_quantity_is_at_least_1_to_purchase_tickets()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->response = $this->orderTickets($concert,[
            'email' => 'john@example.com',
            'ticket_quantity' => 0,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('ticket_quantity');
    }

    /** @test */
    public function payment_token_is_required()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->response = $this->orderTickets($concert,[
            'email' => 'john@example.com',
            'ticket_quantity' => 1,
        ]);

        $this->assertValidationError('payment_token');
    }

    /** @test */
    public function cannot_purchase_tickets_to_an_unpublished_concert()
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->states('unpublished')->create()->addTickets(3);

        $this->response = $this->orderTickets($concert,[
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->response->assertStatus(404);
        self::assertFalse($concert->hasOrdersFor('john@example.com'));
        self::assertEquals(0, $this->paymentGateway->totalCharges());
    }

    /** @test */
    public function an_order_is_not_created_if_payment_fails()
    {
        $concert = factory(Concert::class)->states('published')->create([
            'ticket_price' => 3250,
        ])->addTickets(3);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token',
        ]);

        $response->assertStatus(422);
        $order = $concert->orders()->where('email', 'john@example.com')->first();
        self::assertNull($order);
    }

    /** @test */
    public function cannot_purchase_more_tickets_than_remain()
    {
        $concert = factory(Concert::class)->states('published')->create()->addTickets(50);

        $response = $this->orderTickets($concert,[
            'email' => 'john@example.com',
            'ticket_quantity' => 51,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(422);
        self::assertFalse($concert->hasOrderFor('john@example.com'));
        self::assertEquals(0, $this->paymentGateway->totalCharges());
        self::assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function cannot_purchase_tickets_another_customer_is_already_trying_to_purchase()
    {
        $this->disableExceptionHandling();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->states('published')->create([
            'ticket_price' => 1200
        ])->addTickets(3);

        $this->paymentGateway->beforeFirstCharge(function ($paymentGateway) use ($concert) {
            $response = $this->orderTickets($concert, [
                'email' => 'personB@example.com',
                'ticket_quantity' => 1,
                'payment_token' => $this->paymentGateway->getValidTestToken(),
            ]);

            $response->assertStatus(422);
            $this->assertFalse($concert->hasOrderFor('personB@example.com'));
            $this->assertEquals(0, $this->paymentGateway->totalCharges());
        });

        $response = $this->orderTickets($concert, [
            'email' => 'personA@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        self::assertEquals(3600, $this->paymentGateway->totalCharges());
        self::assertTrue($concert->hasOrderFor('personA@example.com'));
        self::assertEquals(3, $concert->ordersFor('personA@example.com')->first()->ticketQuantity());
    }
}
