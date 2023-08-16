<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Events\NewOrderCreated;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sku;
use Filament\Pages\Actions;
use Filament\Pages\Actions\Action;
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
            $items = $data['items'];

            $totalPaypable = Order::totalPayable($items);
            $totalSalable = Order::totalSubtotals($items);
            $profit = (int) $totalSalable - (int) $totalPaypable;

            $orderData = [
                'tracking_no' => uniqid($prefix),
                'reseller_id' => auth()->user()->id,
                'status' => OrderStatus::WaitingForWholesalerApproval->value,
                "courier_charge" => Order::courierCharge($items),
                "packaging_charge" => Order::packgingCost(),
                "total_payable" => Order::totalPayable($items),
                "total_saleable" => $totalSalable,
                "profit" => $profit,
                ...collect($data)->except([
                    'list',
                    'items',
                    'courier_charge',
                    'packaging_charge',
                    'total_payable',
                    'total_saleable',
                    'profit',
                ])->toArray()
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

    protected function getFormActions(): array
    {
        $formData = $this->form->getRawState();

        return $formData['error'] ? [$this->getCancelFormAction()] : parent::getFormActions();
    }
}
