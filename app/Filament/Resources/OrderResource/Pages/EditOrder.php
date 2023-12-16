<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\Sku;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        $data['list'] = explode('-', $data['tracking_no'])[0];

        $items = Order::with(['items', 'items.sku', 'items.sku.product'])->find($data['id'])
            ->items
            ->map(function ($item) {

                return [
                    'product' => $item->sku->product->id,
                    'sku' => $item->sku_id,
                    'quantity' => $item->quantity,
                    'reseller_price' => $item->reseller_price,
                    'subtotal' => $item->total_amount,
                    'status' => $item->status,
                ];
            })->toArray();

        $data['items'] = $items;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {

        return DB::transaction(function () use ($data, $record) {

            $items =  collect($data['items'])
                ->map(
                    function ($item) {
                        $item['subtotal'] = (int) $item['reseller_price'] * (int) $item['quantity'];
                        return $item;
                    }
                )->toArray();

            $customerId = $data['customer_id'];
            $totalPaypable = Order::totalPayable($items, $customerId);
            $totalSalable = Order::totalSubtotals($items);
            $courier_charge = Order::courierCharge($items, $customerId);
            $profit = (int) $data['cod'] - $totalPaypable;

            $orderData = [
                'courier_charge' => $courier_charge,
                'packaging_charge' => Order::packgingCost(),
                'total_payable' => $totalPaypable,
                'total_saleable' => $totalSalable,
                'profit' => $profit,
                ...collect($data)->except([
                    'list',
                    'items',
                    'courier_charge',
                    'packaging_charge',
                    'total_payable',
                    'total_saleable',
                    'profit',
                ])->toArray(),
            ];

            $record->update($orderData);

            // create items

            collect($items)
                ->each(function ($item) use ($record) {

                    $dbItem = $record->items()->where('sku_id', $item['sku'])->first();

                    if ($dbItem) {
                        if ($dbItem->status == OrderItemStatus::WaitingForWholesalerApproval) {
                            $dbItem->update([
                                'quantity' => $item['quantity'],
                                'reseller_price' => $item['reseller_price'],
                                'total_amount' => (int) $item['subtotal'],
                                'status' => $item['status'] ?? OrderItemStatus::WaitingForWholesalerApproval->value,
                            ]);
                        }
                    } else {

                        $sku = Sku::with('product')->find($item['sku']);

                        $record->items()->create([
                            'product_id' => $sku->product_id,
                            'sku_id' => $sku->id,
                            'quantity' => $item['quantity'],
                            'wholesaler_price' => $sku->price,
                            'wholesaler_id' => $sku->product->owner_id,
                            'reseller_price' => $item['reseller_price'],
                            'total_amount' => (int) $item['subtotal'],
                            'status' => OrderItemStatus::WaitingForWholesalerApproval->value,
                        ]);
                    }
                });

            // delete old skus

            $skus = collect($items)->pluck('sku')->toArray();
            $record->refresh()->items()->whereNotIn('sku_id', $skus)->delete();

            return $record;
        });
    }

    protected function getFormActions(): array
    {
        $formData = $this->form->getRawState();

        return $formData['error'] ? [$this->getCancelFormAction()] : parent::getFormActions();
    }
}
