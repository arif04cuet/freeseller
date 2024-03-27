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

    #[Locked]
    #[Validate('required')]
    public $customerId = null;

    #[On('show-cart')]
    public function open()
    {
        $this->open = true;
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

            $order = Order::create([
                'tracking_no' => uniqid(),
                'reseller_id' => auth()->user()->id,
                'status' => OrderStatus::WaitingForWholesalerApproval->value,
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
            ]);

            $items = Busket::content()
                ->map(function ($item, $skuId) {

                    $sku = Sku::with('product')->find($skuId);

                    return [
                        'product_id' => $sku->product_id,
                        'sku_id' => $sku->id,
                        'quantity' => $item->quantity,
                        'wholesaler_price' => $sku->price,
                        'wholesaler_id' => $sku->product->owner_id,
                        'reseller_price' => $item->price,
                        'total_amount' => (int) $item->price * (int) $item->quantity,
                        'status' => OrderItemStatus::WaitingForWholesalerApproval->value,
                    ];
                })->toArray();

            $order->items()->createMany($items);

            NewOrderCreated::dispatch($order);

            $this->clearCart();
            $this->close();
            $this->dispatch('success', message: "Order#" . $order->id . " created successfully!");
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
