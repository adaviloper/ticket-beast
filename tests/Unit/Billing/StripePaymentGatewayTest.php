<?php

namespace Tests\Unit\Billing;

use App\Billing\StripePaymentGateway;
use Tests\TestCase;

/**
 * @group Integration
 */
class StripePaymentGatewayTest extends TestCase
{
    use PaymentGatewayContractTests;

    protected function getPaymentGateway()
    {
        return new StripePaymentGateway(config('services.stripe.secret'));
    }

    /** @test */
    public function ninety_percent_of_the_payment_is_transferred_to_the_destination_account(): void
    {
        $paymentGateway = $this->getPaymentGateway();

        $paymentGateway->charge(5000, $paymentGateway->getValidTestToken(), env('STRIPE_TEST_PROMOTER_ID'));

        $lastStripeCharge = array_first(\Stripe\Charge::all(
            ['limit' => 1], ['api_key' => config('services.stripe.secret')]
        )['data']);

        self::assertEquals(5000, $lastStripeCharge['amount']);
        self::assertEquals(env('STRIPE_TEST_PROMOTER_ID'), $lastStripeCharge['destination']);

        $transfer = \Stripe\Transfer::retrieve($lastStripeCharge['transfer'], config('services.stripe.secret'));
        self::assertEquals(4500, $transfer['amount']);
    }
}
