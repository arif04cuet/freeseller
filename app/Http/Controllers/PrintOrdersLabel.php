<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class PrintOrdersLabel extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

        $orderIds = explode(',', $request->get('orders', ''));

        $orders = Order::query()->with([
            'items',
            'items.sku',
            'items.sku',
            'reseller',
            'reseller.business',
            'reseller.address',
            'customer',

        ])
            ->whereIn('id', $orderIds)
            ->orderBy('id', 'asc')
            ->get();

        return view('orders.new-print-invoices', compact('orders'));
    }
}
