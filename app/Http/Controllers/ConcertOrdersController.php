<?php

namespace App\Http\Controllers;

use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;
use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
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
        /** @var Concert $concert */
        $concert = Concert::published()->findOrFail($concertId);

        $this->validate(request(), [
            'email' => 'required|email',
            'payment_token' => 'required',
            'ticket_quantity' => 'required|integer|min:1',
        ]);

        try {
            $order = $concert->orderTickets(request('email'), request('ticket_quantity'));
            $this->paymentGateway->charge(
                request('ticket_quantity') * $concert->ticket_price,
                request('payment_token')
            );

            return response([], Response::HTTP_CREATED);
        } catch (PaymentFailedException $exception) {
            $order->cancel();
            return response([], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (NotEnoughTicketsException $exception) {
            return response([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
