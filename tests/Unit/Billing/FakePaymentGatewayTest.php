<?php

namespace Tests\Unit\Billing;

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;
use Tests\TestCase;

class FakePaymentGatewayTest extends TestCase
{
    protected function getPaymentGateway()
    {
        return new FakePaymentGateway();
    }

    /** @test */
    public function charges_with_a_valid_payment_token_are_successful()
    {
        $paymentGateway = $this->getPaymentGateway();

        $newCharges = $paymentGateway->newChargesDuring(static function ($paymentGateway) {
            $paymentGateway->charge(2500, $paymentGateway->getValidTestToken());
        });

        self::assertCount(1, $newCharges);
        self::assertEquals(2500, $newCharges->sum());
    }

    /** @test */
    public function charges_with_a_invalid_payment_token_fail()
    {
        try {
            $paymentGateway = new FakePaymentGateway();
            $paymentGateway->charge(2500, 'invalid-payment-token');
        } catch (PaymentFailedException $exception) {
            return;
        }

        self::fail();
    }

    /** @test */
    public function running_a_hook_before_the_first_charge()
    {
        $paymentGateway = new FakePaymentGateway();
        $timesCallbackRan = 0;

        $paymentGateway->beforeFirstCharge(function ($paymentGateway) use (&$timesCallbackRan) {
            $timesCallbackRan++;
            $paymentGateway->charge(2500, $paymentGateway->getValidTestToken());

            $this->assertEquals(2500, $paymentGateway->totalCharges());
        });

        $paymentGateway->charge(2500, $paymentGateway->getValidTestToken());
        self::assertEquals(1, $timesCallbackRan);
        self::assertEquals(5000, $paymentGateway->totalCharges());
    }

    /** @test */
    public function can_fetch_charges_created_during_a_callback()
    {
        $paymentGateway = $this->getPaymentGateway();
        $paymentGateway->charge(2000, $paymentGateway->getValidTestToken());
        $paymentGateway->charge(3000, $paymentGateway->getValidTestToken());

        $newCharges = $paymentGateway->newChargesDuring(static function ($paymentGateway) {
            $paymentGateway->charge(4000, $paymentGateway->getValidTestToken());
            $paymentGateway->charge(5000, $paymentGateway->getValidTestToken());
        });

        self::assertCount(2, $newCharges);
        self::assertEquals([4000, 5000], $newCharges->all());
    }
}
