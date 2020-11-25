<?php

namespace App\Http\Controllers;

use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;
use App\Concert;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConcertOrdersController extends Controller
{
    /**
     * @var PaymentGateway
     */
    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function store($concertId)
    {
        $concert = Concert::published()->findOrFail($concertId);

        $this->validate(request(), [
            'email' => 'required|email',
            'payment_token' => 'required',
            'ticket_quantity' => 'required|integer|min:1',
        ]);

        try {
            $this->paymentGateway->charge(
                request('ticket_quantity') * $concert->ticket_price,
                request('payment_token')
            );

            $concert->orderTickets(request('email'), request('ticket_quantity'));

            return response([], Response::HTTP_CREATED);
        } catch (PaymentFailedException $exception) {
            return response([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
