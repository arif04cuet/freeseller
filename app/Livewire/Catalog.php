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
        'list' => '',
    ];
    #[Url()]
    public $sort = 'new';

    public $perPage = 12;


    #[Computed]
    public function total(): int
    {
        return $this->products->total();
    }


    #[Computed()]
    public function products()
    {
        return Product::query()
            ->select(['id', 'name', 'category_id', 'price', 'offer_price'])
            ->has('skus')
            ->search($this->search)
            ->filter($this->filters)
            ->sort($this->sort)
            ->withSum('skus', 'quantity')
            ->withSum('orderItems', 'quantity')
            ->with(['media', 'category', 'skus.firstMedia'])
            ->paginate($this->perPage);
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
    public function resetAll()
    {
        $this->reset();
    }

    public function loadMore()
    {
        $this->perPage += 12;
    }

    public function render()
    {

        return view('livewire.catalog', [
            'products' => $this->products
        ])
            ->layoutData([
                'title' => 'সকল প্রোডাক্ট ',
                'description' => collect($this->categories)->implode(',')
            ]);
    }
}
