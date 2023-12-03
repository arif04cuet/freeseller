<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Events\NewOrderCreated;
use App\Filament\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Models\Sku;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
            ->requiresConfirmation()
            ->modalIconColor('success')
            ->visible(fn () => empty($this->form->getRawState()['error']))
            ->modalHeading('Order Summary')
            ->modalDescription(
                function (Action $action) {
                    try {
                        $data = $this->form->getState();
                    } catch (\Throwable $th) {
                        return;

                        return 'Error. Fill all the required fields';

                        //$action->cancel();
                    }


                    return new HtmlString(view('order.overview', $data)->render());
                }
            )
            //->action(fn () => $this->create())
            ->action(fn () => dd('ok'))
            ->before(function () {

                // 10 seceond wait time for a order of same reseller
                $lock = Cache::lock(auth()->user()->id . ':order:create', 10);
                if (!$lock->get()) {
                    return redirect()->route('filament.app.resources.orders.index');
                }
            })
            ->keyBindings(['mod+s']);
    }


    public function getDefaultValues(): array
    {
        $request = request();

        $data = [];

        $data['list'] = $request->get('list', 0);
        $data['hub_id'] = Address::query()->hubs()->first()->id;
        $items = $request->get('skus', '');
        if ($items)
            $data['items'] = collect(explode(',', $items))
                ->filter(fn ($item) => count(explode('-', $item)) == 2)
                ->map(
                    function ($item) {
                        list($product, $sku) = explode('-', $item);
                        return [
                            "product" => $product,
                            "sku" => $sku,
                            "quantity" => 1,
                            "reseller_price" => null,
                            "subtotal" => null,
                            "status" => null
                        ];
                    }
                )->toArray();

        return $data;
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $this->form->fill($this->getDefaultValues());

        $this->callHook('afterFill');
    }

    public function beforeFill()
    {
        $user = auth()->user();

        $lockAmount = (int) $user->lockAmount->sum('amount');
        $balance = $user->balanceFloat - $lockAmount;

        $minimum_amount = config('freeseller.minimum_acount_balance');
        if ($minimum_amount && ($balance < $minimum_amount)) {

            Notification::make()
                ->title('Your current balance is below ' . $minimum_amount . '. please recharge to make order')
                ->danger()
                ->persistent()
                ->send();

            return redirect()->to(route('filament.app.resources.orders.index'));
        }
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {

        return DB::transaction(function () use ($data) {

            $prefix = $data['list'] . '-';
            $items = collect($data['items'])
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
                'tracking_no' => uniqid($prefix),
                'reseller_id' => auth()->user()->id,
                'status' => OrderStatus::WaitingForWholesalerApproval->value,
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

            $order = $this->getModel()::create($orderData);

            // create items

            $items = collect($items)
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

    // protected function getFormActions(): array
    // {
    //     $formData = $this->form->getRawState();

    //     return $formData['error'] ? [$this->getCancelFormAction()] : parent::getFormActions();
    // }
}
