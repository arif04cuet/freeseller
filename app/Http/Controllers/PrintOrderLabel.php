<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class PrintOrderLabel extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Order $order)
    {
        $order->loadMissing(['items.sku', 'reseller']);

        return view('orders.print-address', compact('order'));
    }
}
