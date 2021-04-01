<?php

namespace App\Billing;

use Stripe\Exception\InvalidRequestException;
use Stripe\Token;

class StripePaymentGateway implements PaymentGateway
{
    public const TEST_CARD_NUMBER = '4242424242424242';

    private $apiKey;

    /**
     * StripePaymentGateway constructor.
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getValidTestToken($cardNumber = self::TEST_CARD_NUMBER)
    {
        return Token::create(
            [
                'card' => [
                    'number' => $cardNumber,
                    'exp_month' => 11,
                    'exp_year' => 2021,
                    'cvc' => '314',
                ],
            ],
            ['api_key' => $this->apiKey]
        )->id;
    }

    public function charge($amount, $token, $destinationAccountId)
    {
        try {
            $stripeCharge = \Stripe\Charge::create(
                [
                    'amount' => $amount,
                    'source' => $token,
                    'currency' => 'USD',
                    'destination' => [
                        'account' => $destinationAccountId,
                        'amount' => $amount * .9,
                    ]
                ],
                ['api_key' => $this->apiKey]
            );

            return new Charge([
                'amount' => $stripeCharge['amount'],
                'card_last_four' => $stripeCharge['source']['last4'],
                'destination' => $destinationAccountId,
            ]);
        } catch (InvalidRequestException $exception) {
            throw new PaymentFailedException;
        }
    }

    private function lastCharge()
    {
        return array_first(\Stripe\Charge::all(
            ['limit' => 1], ['api_key' => $this->apiKey]
        )['data']);
    }

    private function newChargesSince($charge = null)
    {
        $newCharges = \Stripe\Charge::all(
            [
                'ending_before' => $charge ? $charge->id : null,
            ],
            ['api_key' => $this->apiKey]
        )['data'];

        return collect($newCharges);
    }

    public function newChargesDuring($callback)
    {
        $lastCharge = $this->lastCharge();
        $callback($this);
        return $this->newChargesSince($lastCharge)
            ->map(static function ($stripeCharge) {
                return new Charge([
                    'amount' => $stripeCharge['amount'],
                    'card_last_four' => $stripeCharge['source']['last4'],
                ]);
            });
    }
}
