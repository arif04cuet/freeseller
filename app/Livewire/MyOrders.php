<?php

namespace App\Livewire;

use App\Models\Order;
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


    public function transactions($orderId)
    {
        $this->transactionOrder = $this->orders->where('id', $orderId)->first();
        $this->isModalOpen = true;

        $this->dispatch('open-modal', id: 'transaction_modal');
    }

    #[Computed()]
    public function orders()
    {
        return Order::query()
            ->with(['customer'])
            ->withCount(['items' => fn ($q) => $q->active()])
            ->withSum(['items' => fn ($query) => $query->active()], 'wholesaler_price')
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

        return view('livewire.my-orders');
    }
}
