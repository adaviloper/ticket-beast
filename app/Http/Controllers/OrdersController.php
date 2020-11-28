<?php

namespace App\Http\Controllers;

use App\Order;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function show($orderConfirmation)
    {
        return view('orders.show', [
            'order' => Order::where('confirmation_number', $orderConfirmation)->first(),
        ]);
    }
}
