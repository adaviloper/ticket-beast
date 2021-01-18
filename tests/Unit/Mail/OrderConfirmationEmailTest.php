<?php

namespace Tests\Unit\Mail;

use App\Mail\OrderConfirmationEmail;
use App\Order;

class OrderConfirmationEmailTest extends \Tests\TestCase
{
    /** @test */
    public function email_contains_a_link_to_the_order_confirmation_page(): void
    {
        $order = factory(Order::class)->make([
            'confirmation_number' => self::GOOD_ORDER_CONFIRMATION_NUMBER,
        ]);
        $email = new OrderConfirmationEmail($order);
        $rendered = $this->render($email);

        self::assertContains(url('/orders/' . self::GOOD_ORDER_CONFIRMATION_NUMBER), $rendered);
    }

    /** @test */
    public function email_has_a_subject(): void
    {
        $order = factory(Order::class)->make();
        $email = new OrderConfirmationEmail($order);

        self::assertEquals('Your TicketBeast Order', $email->build()->subject);
    }

    private function render($mailable)
    {
        $mailable->build();
        return view($mailable->view, $mailable->buildViewData())->render();
    }
}
