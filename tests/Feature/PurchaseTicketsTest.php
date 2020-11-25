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
        $this->assertArrayHasKey($field, $this->response->decodeResponseJson());
    }

    /** @test */
    public function customer_can_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->states('published')->create([
            'ticket_price' => 3250,
        ]);
        $concert->addTickets(3);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(201);

        $this->assertEquals(9750, $this->paymentGateway->totalCharges());

        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals(3, $order->tickets()->count());
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
        $concert = factory(Concert::class)->states('unpublished')->create();
        $concert->addTickets(3);

        $this->response = $this->orderTickets($concert,[
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->response->assertStatus(404);
        $this->assertEquals(0, $concert->orders()->count());
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
    }

    /** @test */
    public function an_order_is_not_created_if_payment_fails()
    {
        $this->disableExceptionHandling();
        $concert = factory(Concert::class)->states('published')->create([
            'ticket_price' => 3250,
        ]);
        $concert->addTickets(3);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token',
        ]);

        $response->assertStatus(422);
        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNull($order);
    }

    /** @test */
    public function cannot_purchase_more_tickets_than_remain()
    {
        $this->disableExceptionHandling();
        $concert = factory(Concert::class)->states('published')->create();
        $concert->addTickets(50);

        $response = $this->orderTickets($concert,[
            'email' => 'john@example.com',
            'ticket_quantity' => 51,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(422);
        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNull($order);
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
        $this->assertEquals(50, $concert->ticketsRemaining());
    }
}
