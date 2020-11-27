<?php

namespace Tests\Unit\Billing;

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentFailedException;
use App\Billing\StripePaymentGateway;
use Stripe\Charge;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * @group Integration
 */
class StripePaymentGatewayTest extends TestCase
{
    use PaymentGatewayContractTests;

    protected $lastCharge;

    protected function setUp()
    {
        parent::setUp();

        $this->lastCharge = $this->lastCharge();
    }

    private function lastCharge()
    {
        return array_first(Charge::all(
            ['limit' => 1], ['api_key' => config('services.stripe.secret')]
        )['data']);
    }

    private function newCharges()
    {
        return Charge::all(
            [
                'ending_before' => $this->lastCharge ? $this->lastCharge->id : null,
            ],
            ['api_key' => config('services.stripe.secret')]
        )['data'];
    }

    private function validToken()
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        return $stripe->tokens->create(
            [
                'card' => [
                    'number' => '4242424242424242',
                    'exp_month' => 11,
                    'exp_year' => 2021,
                    'cvc' => '314',
                ],
            ]
        )->id;
    }

    protected function getPaymentGateway()
    {
        return new StripePaymentGateway(config('services.stripe.secret'));
    }

    /** @test */
    public function charges_with_a_invalid_payment_token_fail()
    {
        try {
            $paymentGateway = new StripePaymentGateway(config('services.stripe.secret'));
            $paymentGateway->charge(2500, 'invalid-payment-token');
        } catch (PaymentFailedException $exception) {
            self::assertCount(0, $this->newCharges());
            return;
        }

        self::fail('Charging with an invalid payment token did not throw a PaymentFailedException.');
    }

}
