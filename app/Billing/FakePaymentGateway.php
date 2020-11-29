<?php

namespace App\Billing;

class FakePaymentGateway implements PaymentGateway
{
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

    public function getValidTestToken($cardNumber = '4242424242424242')
    {
        return tap('fake-token_' . str_random(24), function ($token) use ($cardNumber) {
            $this->tokens->put($token, $cardNumber);
        });
    }

    public function charge($amount, $token)
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
        ]);
    }

    public function totalCharges()
    {
        return $this->charges
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
