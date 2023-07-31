<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Events\NewOrderCreated;
use App\Filament\Resources\OrderResource;
use App\Models\Product;
use App\Models\Sku;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {

        return DB::transaction(function () use ($data) {


            $prefix = $data['list'] . '-';

            $orderData = [
                'tracking_no' => uniqid($prefix),
                'reseller_id' => auth()->user()->id,
                'customer_id' => $data['customer_id'],
                'total_amount' => $data['total_amount'],
                'note' => $data['note'],
                'status' => OrderStatus::WaitingForWholesalerApproval->value
            ];

            $order = $this->getModel()::create($orderData);

            // create items

            $items = collect($data['items'])
                ->map(function ($item) {

                    $sku = Sku::with('product')->find($item['sku']);

                    return [
                        'product_id' => $sku->product_id,
                        'sku_id' => $sku->id,
                        'quantity' => $item['quantity'],
                        'wholesaler_price' => $sku->price,
                        'wholesaler_id' => $sku->product->owner_id,
                        'reseller_price' => $item['reseller_price'],
                        'total_amount' => $item['subtotal'],
                        'status' => OrderItemStatus::WaitingForWholesalerApproval->value,
                    ];
                })->toArray();

            $order->items()->createMany($items);

            NewOrderCreated::dispatch($order);

            return $order;
        });
    }
}
