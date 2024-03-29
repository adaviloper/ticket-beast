<?php

namespace App\Billing;

class FakePaymentGateway implements PaymentGateway
{
    public const TEST_CARD_NUMBER = '4242424242424242';

    private $beforeFirstChargeCallback;

    /** @var \Illuminate\Support\Collection */
    private $charges;

    /** @var \Illuminate\Support\Collection */
    private $tokens;

    public function __construct()
    {
        $this->charges = collect();
        $this->tokens = collect();
    }

    public function getValidTestToken($cardNumber = self::TEST_CARD_NUMBER)
    {
        return tap('fake-token_' . str_random(24), function ($token) use ($cardNumber) {
            $this->tokens->put($token, $cardNumber);
        });
    }

    public function charge($amount, $token, $destinationAccountId)
    {
        if ($this->beforeFirstChargeCallback !== null) {
            $callback = $this->beforeFirstChargeCallback;
            $this->beforeFirstChargeCallback = null;
            $callback($this);
        }

        if (!$this->tokens->has($token)) {
            throw new PaymentFailedException;
        }

        return $this->charges[] = new Charge([
            'amount' => $amount,
            'card_last_four' => substr($this->tokens[$token], -4),
            'destination' => $destinationAccountId,
        ]);
    }

    public function totalCharges()
    {
        return $this->charges
            ->map
            ->amount()
            ->sum();
    }

    public function totalChargesFor($accountId)
    {
        return $this->charges
            ->filter(static function ($charge) use ($accountId) {
                return $charge->destination() === $accountId;
            })
            ->map
            ->amount()
            ->sum();
    }

    public function beforeFirstCharge($callback)
    {
        $this->beforeFirstChargeCallback = $callback;
    }

    public function newChargesDuring($callback)
    {
        $chargesFrom = $this->charges->count();
        $callback($this);
        return $this->charges
            ->slice($chargesFrom)
            ->reverse()
            ->values();
    }
}
