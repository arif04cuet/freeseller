<?php

namespace App\Livewire;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Events\NewOrderCreated;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Sku;
use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;
use Busket;
use DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;

use function App\Helpers\money;

class Cart extends Component
{

    public $open = false;
    public $cod = '';
    public $note_for_wholesaler = '';
    public $note_for_courier = '';

    public $title = 'New Order';

    #[Locked]
    #[Validate('required')]
    public $customerId = null;

    #[Locked]
    public $editOrderId = null;

    #[On('show-cart')]
    public function open()
    {
        $this->open = true;
        if ($this->isOrderEdit())
            $this->editOrder(session('editOrderId'), false);
    }

    public function isOrderEdit()
    {
        return session()->has('editOrderId');
    }

    public function clearEdit()
    {
        $this->editOrderId = null;
        $this->clearCart();
        $this->close();

        session()->forget('editOrderId');
    }

    #[On('edit-order')]
    public function editOrder($id, $clear = true)
    {
        session(['editOrderId' => $id]);

        $order = Order::query()->with('items.sku')->find($id);

        $this->title = 'Edit Order #' . $id;
        $this->editOrderId = $id;
        $this->customerId = $order->customer_id;
        $this->cod = $order->cod;
        $this->note_for_courier = $order->note_for_courier;
        $this->note_for_wholesaler = $order->note_for_wholesaler;

        $clear && $this->clearCart();

        foreach ($order->items as $item) {
            $sku = $item->sku->loadMissing(['product', 'media']);
            Busket::add(
                $sku->id,
                $sku->product->name,
                $item->wholesaler_price,
                $item->quantity,
                ['image' => $sku->getMedia('sharees')->first()->getUrl('thumb')]
            );
        }
    }


    public function close()
    {
        $this->open = false;
        $this->reset();
    }

    public function createOrder()
    {

        $this->validate();

        $courier_charge = (int) Busket::deliveryCharge();
        $packaging_charge = (int)  Order::packgingCost();
        $totalPaypable = (int) Busket::total() + $courier_charge + $packaging_charge;
        $cod = (int) $this->cod;
        $profit =  $cod - $totalPaypable;

        DB::beginTransaction();

        try {

            $orderData  = [
                'courier_charge' => $courier_charge,
                'packaging_charge' => $packaging_charge,
                'total_payable' => $totalPaypable,
                'total_saleable' => $totalPaypable,
                'profit' => $profit,
                'note_for_wholesaler' => $this->note_for_wholesaler,
                'note_for_courier' => $this->note_for_courier,
                'cod' => $cod,
                'hub_id' => $this->hub->id,
                'customer_id' => $this->customerId
            ];

            if (!$this->isOrderEdit()) {

                $orderData['tracking_no'] = uniqid();
                $orderData['reseller_id'] = auth()->user()->id;
                $orderData['status'] = OrderStatus::WaitingForWholesalerApproval->value;
            }

            $order = $this->isOrderEdit() ?
                tap(Order::with('items')->find($this->editOrderId))->update($orderData) :
                Order::create($orderData);

            $skusIds = $this->isOrderEdit() ? $order->items->pluck('sku_id')->toArray() : [];

            // delete abandond items
            if ($this->isOrderEdit())
                $order->items()->whereNotIn('sku_id', Busket::content()->keys()->toArray())->delete();

            $items = Busket::content()
                ->each(function ($item, $skuId) use ($skusIds, $order) {

                    $skuData = [
                        'quantity' => $item->quantity,
                        'reseller_price' => $item->price,
                        'total_amount' => (int) $item->price * (int) $item->quantity,
                    ];

                    if (in_array($skuId, $skusIds))
                        $order->items->where('sku_id', $skuId)->first()->update($skuData);
                    else {

                        $sku = Sku::with('product')->find($skuId);
                        $order->items()->create([
                            ...$skuData,
                            'product_id' => $sku->product_id,
                            'sku_id' => $sku->id,
                            'wholesaler_id' => $sku->product->owner_id,
                            'wholesaler_price' => $sku->price,
                            'status' => OrderItemStatus::WaitingForWholesalerApproval->value,
                        ]);
                    }
                });




            if (!$this->isOrderEdit()) {
                NewOrderCreated::dispatch($order);
                $message = "Order#" . $order->id . " created successfully!";
            } else {
                $message = "Order#" . $order->id . " updated successfully!";
                $this->clearEdit();
            }

            $this->clearCart();
            $this->close();
            $this->dispatch('success', message: $message);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            logger($e->getMessage());
        }
    }

    #[Computed(persist: true)]
    public function hub()
    {
        return Address::query()->where('type', 'hub')->first();
    }

    #[Computed(persist: true)]
    public function canPlaceOrder()
    {
        return auth()->user()->canPlaceOrder();
    }

    #[On('customerSelected')]
    public function selectedCustomer($id)
    {
        $this->customerId = $id;
    }

    #[On('productAddedToCart')]
    public function render()
    {

        $content = Busket::content();

        return view('livewire.cart', [
            'total' => Busket::total(),
            'content' => $content,
            'brandingCharge' => Order::packgingCost(),
            'deliveryCharge' => Busket::deliveryCharge($this->customerId)
        ]);
    }

    public function removeFromCart(string $id): void
    {
        Busket::remove($id);
        $this->dispatch('CartItemRemoved', ['id' => $id]);
    }

    public function clearCart(): void
    {
        Busket::clear();
        $this->dispatch('CartCleared');
    }

    public function updateCartItem(string $id, string $action): void
    {
        Busket::update($id, $action);
    }
}
