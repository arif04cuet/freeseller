<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LoadMoreProducts extends Component
{
    use WithPagination;

    public $search;
    public $filters;
    public $sort;

    public $perPage;
    public $page;
    public $loadMore = false;

    public function loadMoreProducts()
    {

        $this->page += 1;
        $this->loadMore = true;
    }
    #[Computed()]
    public function products()
    {
        return Product::query()
            ->search($this->search)
            ->filter($this->filters)
            ->sort($this->sort)
            ->withSum('skus', 'quantity')
            ->with(['media', 'category', 'skus.media'])
            ->paginate($this->perPage, ['*'], null, $this->page);
    }
    public function render()
    {
        if (!$this->loadMore) {
            return view('livewire.load-more-products');
        } else {
            return view('livewire.catalog', [
                'products' => $this->products,
                'page' => $this->page,
                'perPage' => $this->perPage,
                'search' => $this->search,
                'filters' => $this->filters,
                'sort' => $this->sort,
            ]);
        }
    }
}
