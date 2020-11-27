<?php

namespace Unit\Billing;

use Tests\TestCase;

class StripePaymentGatewayTest extends TestCase
{
    /** @test */
    public function charges_with_a_valid_payment_token_are_successful()
    {
        $paymentGateway = new StripePaymentGateway();

        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        $token = $stripe->tokens->create(
            [
                'card' => [
                    'number' => '4242424242424242',
                    'exp_month' => 11,
                    'exp_year' => 2021,
                    'cvc' => '314',
                ],
            ],
            ['api_key' => config('services.stripe.secret')]
        )->id;

        $paymentGateway->charge(2500, $token);

        $lastcharge = \Stripe\Charge::all(
            ['limit' => 1],
            ['api_key' => config('services.stripe.secret')]
        )['data'][0];

        self::assertEquals(2500, $lastcharge->amount);
    }
}
