<?php

namespace App\Http\Controllers;

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;
use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use http\Env\Response;
use Illuminate\Http\Request;

class ConcertOrdersController extends Controller
{
    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function store($concertID)
    {
        $this->validate(\request(), [
            'email' => 'required'
            ]);

        try {
            $concert = Concert::published()->findOrFail($concertID);
            // findOrFail throws 404
            $ticketsQuantity = \request('ticket_quantity');
            $amount = $ticketsQuantity * $concert->ticket_price;

            $order = $concert->orderTickets(\request('email'), $ticketsQuantity);
            $this->paymentGateway->charge($amount, \request('payment_token'));


            return response()->json([], 201);
        } catch (PaymentFailedException $exception) {
            $order->cancel();
            return response()->json([], 422);
        } catch (NotEnoughTicketsException $exception) {
            return response()->json([], 422);
        }
    }
}
