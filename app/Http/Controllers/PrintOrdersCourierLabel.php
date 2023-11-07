<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class PrintOrdersCourierLabel extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

        $orderIds = explode(',', $request->get('orders', ''));

        $orders = Order::query()->with([
            'reseller',
            'reseller.business',
            'customer',
        ])
            ->whereIn('id', $orderIds)
            ->orderBy('id', 'asc')
            ->get();

        return view('orders.print-courier', compact('orders'));
    }
}
