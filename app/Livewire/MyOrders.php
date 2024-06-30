<?php

namespace App\Livewire;

use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MyOrders extends Component
{

    use WithPagination;


    public $isModalOpen = false;
    public $transactionOrder = null;
    public $search = null;


    public function transactions($orderId)
    {
        $this->transactionOrder = $this->orders->where('id', $orderId)->first();
        $this->isModalOpen = true;

        $this->dispatch('open-modal', id: 'transaction_modal');
    }

    #[Computed()]
    public function orders()
    {
        $search = $this->search;
        return Order::query()
            ->with(['customer'])
            ->when(
                $search,
                fn ($q) => $q->where(
                    fn ($q) => $q->where('id', $search)->orWhereHas(
                        'customer',
                        fn ($q) => $q->where('mobile', $search)
                    )
                )
            )
            ->addSelect([
                'items_sum_wholesaler_price' => OrderItem::query()
                    ->whereColumn('order_id', 'orders.id')
                    ->active()
                    ->selectRaw('SUM(wholesaler_price * quantity) as total_wholesale')
            ])
            ->whereIn('status', [
                OrderStatus::WaitingForWholesalerApproval->value,
                OrderStatus::Processing->value,
                OrderStatus::WaitingForHubCollection->value,
                OrderStatus::HandOveredToCourier->value,
            ])
            ->withSum(
                ['items' => fn ($q) => $q->active()],
                'quantity'
            )

            ->mine()
            ->latest()
            ->paginate(10);
    }
    public function edit($orderId)
    {
        $this->dispatch('show-cart');
        $this->dispatch('edit-order', id: $orderId);
    }
    public function render()
    {
        logger($this->search);
        return view('livewire.my-orders');
    }
}
