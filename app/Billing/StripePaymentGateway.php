<?php

namespace App\Billing;

use Stripe\Charge;
use Stripe\Exception\InvalidRequestException;
use Stripe\Token;

class StripePaymentGateway implements PaymentGateway
{
    private $apiKey;

    /**
     * StripePaymentGateway constructor.
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getValidTestToken()
    {
        return Token::create(
            [
                'card' => [
                    'number' => '4242424242424242',
                    'exp_month' => 11,
                    'exp_year' => 2021,
                    'cvc' => '314',
                ],
            ],
            ['api_key' => $this->apiKey]
        )->id;
    }

    public function charge($amount, $token)
    {
        try {
            Charge::create(
                [
                    'amount' => $amount,
                    'source' => $token,
                    'currency' => 'USD',
                ],
                ['api_key' => $this->apiKey]
            );
        } catch (InvalidRequestException $exception) {
            throw new PaymentFailedException;
        }
    }
}
