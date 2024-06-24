<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SimilarProducts extends Component
{
    public Product $product;

    #[Computed()]
    public function similarProducts()
    {
        return Product::query()
            ->select(['id', 'name', 'category_id', 'price', 'offer_price'])
            ->has('skus')
            ->where('category_id', $this->product->category_id)
            ->where('id', '!=', $this->product->id)
            ->withSum('skus', 'quantity')
            ->withSum('orderItems', 'quantity')
            ->with(['media', 'category', 'skus.firstMedia'])
            ->limit(4)
            ->inRandomOrder()
            ->get();
    }

    public function render()
    {
        return view('livewire.similar-products');
    }
}
