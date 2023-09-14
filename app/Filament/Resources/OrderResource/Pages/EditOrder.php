<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Sku;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        $data['list'] = explode('-', $data['tracking_no'])[0];

        $items = Order::with('items')->find($data['id'])
            ->items
            ->map(function ($item) {

                return [
                    'product' => $item->sku->product->id,
                    'sku' => $item->sku_id,
                    'quantity' => $item->quantity,
                    'reseller_price' => $item->reseller_price,
                    'subtotal' => $item->total_amount,
                    'status' => $item->status
                ];
            })->toArray();

        $data['items'] = $items;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {


        return DB::transaction(function () use ($data, $record) {


            $items = $data['items'];

            $totalPaypable = Order::totalPayable($items);
            $totalSalable = Order::totalSubtotals($items);
            $profit = (int) $totalSalable - (int) $totalPaypable;

            $orderData = [
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

            $record->update($orderData);

            // create items


            collect($data['items'])
                ->each(function ($item) use ($record) {

                    $dbItem = $record->items()->where('sku_id', $item['sku'])->first();

                    if ($dbItem) {
                        $dbItem->update([
                            'quantity' => $item['quantity'],
                            'reseller_price' => $item['reseller_price'],
                            'total_amount' => (int) $item['subtotal'],
                            'status' => $item['status'] ?? OrderStatus::WaitingForWholesalerApproval->value
                        ]);
                    } else {

                        $sku = Sku::with('product')->find($item['sku']);

                        $record->items()->create([
                            'product_id' => $sku->product_id,
                            'sku_id' => $sku->id,
                            'quantity' => $item['quantity'],
                            'wholesaler_price' => $sku->price,
                            'wholesaler_id' => $sku->product->owner_id,
                            'reseller_price' => $item['reseller_price'],
                            'total_amount' => (int)$item['subtotal'],
                            'status' => OrderItemStatus::WaitingForWholesalerApproval->value,
                        ]);
                    }
                });

            // delete old skus

            // $skus = collect($data)->pluck('sku')->toArray();
            // $record->refresh()->items->each(
            //     fn ($item) => in_array(
            //         $item->sku_id,
            //         $skus
            //     ) ? 0 : $item->delete()
            // );

            return $record;
        });
    }

    protected function getFormActions(): array
    {
        $formData = $this->form->getRawState();

        return $formData['error'] ? [$this->getCancelFormAction()] : parent::getFormActions();
    }
}
