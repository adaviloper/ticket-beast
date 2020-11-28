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
}
