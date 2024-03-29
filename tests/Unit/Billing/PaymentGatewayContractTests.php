<?php

namespace Tests\Unit\Billing;

use App\Billing\PaymentFailedException;
use Tests\TestCase;

trait PaymentGatewayContractTests
{
    abstract protected function getPaymentGateway();

    /** @test */
    public function charges_with_a_valid_payment_token_are_successful()
    {
        $paymentGateway = $this->getPaymentGateway();

        $newCharges = $paymentGateway->newChargesDuring(static function ($paymentGateway) {
            $paymentGateway->charge(2500, $paymentGateway->getValidTestToken(), env('STRIPE_TEST_PROMOTER_ID'));
        });

        self::assertCount(1, $newCharges);
        self::assertEquals(2500, $newCharges->map->amount()->sum());
    }

    /** @test */
    public function can_fetch_charges_created_during_a_callback()
    {
        $paymentGateway = $this->getPaymentGateway();
        $paymentGateway->charge(2000, $paymentGateway->getValidTestToken(), env('STRIPE_TEST_PROMOTER_ID'));
        $paymentGateway->charge(3000, $paymentGateway->getValidTestToken(), env('STRIPE_TEST_PROMOTER_ID'));

        $newCharges = $paymentGateway->newChargesDuring(static function ($paymentGateway) {
            $paymentGateway->charge(4000, $paymentGateway->getValidTestToken(), env('STRIPE_TEST_PROMOTER_ID'));
            $paymentGateway->charge(5000, $paymentGateway->getValidTestToken(), env('STRIPE_TEST_PROMOTER_ID'));
        });

        self::assertCount(2, $newCharges);
        self::assertEquals([5000, 4000], $newCharges->map->amount()->all());
    }

    /** @test */
    public function charges_with_a_invalid_payment_token_fail(): void
    {
        $paymentGateway = $this->getPaymentGateway();

        $newCharges = $paymentGateway->newChargesDuring(static function ($paymentGateway) {
            try {
                $paymentGateway->charge(2500, 'invalid-payment-token', env('STRIPE_TEST_PROMOTER_ID'));
            } catch (PaymentFailedException $exception) {
                return;
            }
            self::fail('Charging with an invalid payment token did not throw a PaymentFailedException.');
        });

        self::assertCount(0, $newCharges);
    }

    /** @test */
    public function can_get_details_about_a_successful_charge(): void
    {
        $paymentGateway = $this->getPaymentGateway();

        $charge = $paymentGateway->charge(2500, $paymentGateway->getValidTestToken($paymentGateway::TEST_CARD_NUMBER), env('STRIPE_TEST_PROMOTER_ID'));

        self::assertEquals(substr($paymentGateway::TEST_CARD_NUMBER, -4), $charge->cardLastFour());
        self::assertEquals(2500, $charge->amount());
        self::assertEquals(env('STRIPE_TEST_PROMOTER_ID'), $charge->destination());
    }
}
