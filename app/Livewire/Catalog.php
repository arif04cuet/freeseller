<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class Catalog extends Component
{
    use WithPagination;

    public function render()
    {

        $products = Cache::remember('all-products-' . $this->getPage(), 3600, function () {
            return Product::query()
                ->with(['media', 'category', 'skus.media'])
                ->paginate(8);
        });

        return view('livewire.catalog', [
            'products' => $products
        ]);
    }
}
