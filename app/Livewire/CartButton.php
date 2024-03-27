<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;
use Busket;

class CartButton extends Component
{
    #[On('productAddedToCart')]
    #[On('CartItemRemoved')]
    #[On('CartCleared')]
    public function render()
    {
        return view('livewire.cart-button', [
            'product_count' => Busket::content()->count()
        ]);
    }

    public function openCart()
    {
        $this->dispatch('show-cart');
    }
}
