<?php

namespace App\Http\Controllers;

use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;
use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use App\Order;
use App\Reservation;
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
            $reservation = $concert->reserveTickets(request('ticket_quantity'), request('email'));

            $this->paymentGateway->charge(
                $reservation->totalCost(),
                request('payment_token')
            );


            $order = $reservation->complete();

            return response([
                'email' => $order->email,
                'ticket_quantity' => $order->ticketQuantity(),
                'amount' => $order->amount,
            ], Response::HTTP_CREATED);

        } catch (PaymentFailedException $exception) {
            $reservation->cancel();
            return response([], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (NotEnoughTicketsException $exception) {
            return response([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
