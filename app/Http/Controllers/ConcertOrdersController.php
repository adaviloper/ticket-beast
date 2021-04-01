<?php

namespace App\Http\Controllers;

use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;
use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use App\Mail\OrderConfirmationEmail;
use App\Order;
use App\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

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
            $order = $reservation->complete($this->paymentGateway, request('payment_token'), $concert->user->stripe_account_id);

            Mail::to($order->email)->send(new OrderConfirmationEmail($order));

            return response($order, Response::HTTP_CREATED);

        } catch (PaymentFailedException $exception) {
            $reservation->cancel();
            return response([], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (NotEnoughTicketsException $exception) {
            return response([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
