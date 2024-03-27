<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

use function Laravel\Prompts\search;

class Catalog extends Component
{
    use WithPagination;

    #[Url()]
    public $search = '';
    #[Url()]
    public $filters = [
        'cat' => '',
        'list' => ''
    ];


    #[Computed()]
    public function products()
    {
        return Product::query()
            ->search($this->search)
            ->filter($this->filters)
            ->with(['media', 'category', 'skus.media'])
            ->paginate(8);
    }
    #[Computed(persist: true)]
    public function categories()
    {
        return Category::query()
            ->has('products')
            ->select('id', 'name')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
    public function render()
    {

        // $products = Cache::remember('all-products-' . $this->getPage(), 3600, function () {
        //     return Product::query()
        //         ->with(['media', 'category', 'skus.media'])
        //         ->paginate(8);
        // });

        return view('livewire.catalog', [
            'products' => $this->products
        ]);
    }
}
