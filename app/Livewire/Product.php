<?php

namespace App\Livewire;

use App\Models\Product as ModelsProduct;
use Livewire\Component;

class Product extends Component
{
    public ModelsProduct $product;

    function mount($product)
    {
        $this->product = $product
            ->loadMissing(['skus.media']);
    }
    public function render()
    {
        return view('livewire.product');
    }
}
