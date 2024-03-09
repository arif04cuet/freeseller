<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;
use Busket;

use function App\Helpers\money;

class Cart extends Component
{

    public $open = false;

    #[On('show-cart')]
    function open()
    {
        $this->open = true;
    }

    function close()
    {
        $this->open = false;
    }

    #[On('productAddedToCart')]
    public function render()
    {

        return view('livewire.cart', [
            'total' => money(Busket::total()),
            'content' => Busket::content(),
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
