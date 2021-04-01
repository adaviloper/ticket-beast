<?php

namespace Tests\Feature;

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;
use App\Concert;
use App\Facades\OrderConfirmationNumber;
use App\Facades\TicketCode;
use App\Mail\OrderConfirmationEmail;
use App\User;
use ConcertFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @var FakePaymentGateway
     */
    protected $paymentGateway;

    protected $response;

    public function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = new FakePaymentGateway();
        $this->app->instance(PaymentGateway::class, $this->paymentGateway);
        Mail::fake();
    }

    public function orderTickets($concert, $params): TestResponse
    {
        $savedRequest = $this->app['request'];
        $response = $this->postJson("concerts/{$concert->id}/orders", $params);
        $this->app['request'] = $savedRequest;

        return $response;
    }

    public function assertValidationError($field): void
    {
        $this->response->assertStatus(422);
        self::assertArrayHasKey($field, $this->response->decodeResponseJson()['errors']);
    }

    /** @test */
    public function customer_can_purchase_tickets_to_a_published_concert(): void
    {
        $this->withoutExceptionHandling();

        OrderConfirmationNumber::shouldReceive('generate')->andReturn(self::GOOD_ORDER_CONFIRMATION_NUMBER);
        TicketCode::shouldReceive('generateFor')->andReturn('TICKET_CODE_1', 'TICKET_CODE_2', 'TICKET_CODE_3');

        $user = factory(User::class)->create([
            'stripe_account_id' => self::TEST_STRIPE_ACCOUNT,
        ]);
        $concert = ConcertFactory::createPublished([
            'ticket_price' => 3250,
            'ticket_quantity' => 3,
            'user_id' => $user->id,
        ]);

        $response = $this->orderTickets($concert, [
            'email' => self::JOHN_EMAIL,
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(201);

        $response->assertJson([
            'confirmation_number' => self::GOOD_ORDER_CONFIRMATION_NUMBER,
            'email' => self::JOHN_EMAIL,
            'amount' => 9750,
            'tickets' => [
                ['code' => 'TICKET_CODE_1'],
                ['code' => 'TICKET_CODE_2'],
                ['code' => 'TICKET_CODE_3'],
            ],
        ]);

        self::assertEquals(9750, $this->paymentGateway->totalChargesFor(self::TEST_STRIPE_ACCOUNT));
        self::assertTrue($concert->hasOrdersFor(self::JOHN_EMAIL));
        $order = $concert->ordersFor(self::JOHN_EMAIL)
            ->first();
        self::assertEquals(3, $order->ticketQuantity());

        Mail::assertSent(OrderConfirmationEmail::class, function ($mail) use ($order) {
            return $mail->hasTo(self::JOHN_EMAIL)
                && $mail->order->id === $order->id;
        });
    }

    /** @test */
    public function email_is_required_to_purchase_tickets(): void
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->response = $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('email');
    }

    /** @test */
    public function email_must_be_valid_to_purchase_tickets(): void
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
    public function ticket_quantity_is_required_to_purchase_tickets(): void
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->response = $this->orderTickets($concert,[
            'email' => self::JOHN_EMAIL,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('ticket_quantity');
    }

    /** @test */
    public function ticket_quantity_is_at_least_1_to_purchase_tickets(): void
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->response = $this->orderTickets($concert,[
            'email' => self::JOHN_EMAIL,
            'ticket_quantity' => 0,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('ticket_quantity');
    }

    /** @test */
    public function payment_token_is_required(): void
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->response = $this->orderTickets($concert,[
            'email' => self::JOHN_EMAIL,
            'ticket_quantity' => 1,
        ]);

        $this->assertValidationError('payment_token');
    }

    /** @test */
    public function cannot_purchase_tickets_to_an_unpublished_concert(): void
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->states('unpublished')->create()->addTickets(3);

        $this->response = $this->orderTickets($concert,[
            'email' => self::JOHN_EMAIL,
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->response->assertStatus(404);
        self::assertFalse($concert->hasOrdersFor(self::JOHN_EMAIL));
        self::assertEquals(0, $this->paymentGateway->totalCharges());
    }

    /** @test */
    public function an_order_is_not_created_if_payment_fails(): void
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->states('published')->create([
            'ticket_price' => 3250,
        ])->addTickets(3);

        $response = $this->orderTickets($concert, [
            'email' => self::JOHN_EMAIL,
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token',
        ]);

        $response->assertStatus(422);
        self::assertFalse($concert->hasOrdersFor(self::JOHN_EMAIL));
        self::assertEquals(3, $concert->ticketsRemaining());
    }

    /** @test */
    public function cannot_purchase_more_tickets_than_remain(): void
    {
        /** @var Concert $concert */
        $concert = factory(Concert::class)->states('published')->create()->addTickets(50);

        $response = $this->orderTickets($concert,[
            'email' => self::JOHN_EMAIL,
            'ticket_quantity' => 51,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(422);
        self::assertFalse($concert->hasOrdersFor(self::JOHN_EMAIL));
        self::assertEquals(0, $this->paymentGateway->totalCharges());
        self::assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function cannot_purchase_tickets_another_customer_is_already_trying_to_purchase(): void
    {
        $this->withoutExceptionHandling();
        /** @var Concert $concert */
        $concert = factory(Concert::class)->states('published')->create([
            'ticket_price' => 1200
        ])->addTickets(3);

        $this->paymentGateway->beforeFirstCharge(function ($paymentGateway) use ($concert) {
            $response = $this->orderTickets($concert, [
                'email' => 'personB@example.com',
                'ticket_quantity' => 1,
                'payment_token' => $paymentGateway->getValidTestToken(),
            ]);

            $response->assertStatus(422);
            $this->assertFalse($concert->hasOrdersFor('personB@example.com'));
            $this->assertEquals(0, $paymentGateway->totalCharges());
        });

        $response = $this->orderTickets($concert, [
            'email' => 'personA@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        self::assertEquals(3600, $this->paymentGateway->totalCharges());
        self::assertTrue($concert->hasOrdersFor('personA@example.com'));
        self::assertEquals(3, $concert->ordersFor('personA@example.com')->first()->ticketQuantity());
    }
}
