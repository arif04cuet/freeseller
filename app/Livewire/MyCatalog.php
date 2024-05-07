<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\ResellerList;
use App\Models\Sku;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

use function Laravel\Prompts\search;

class MyCatalog extends Component
{
    use WithPagination;

    #[Url()]
    public $search = '';
    #[Url()]
    public $filters = [
        'cat' => '',
        'list' => ''
    ];
    #[Url()]
    public $sort = '';

    public $perPage = 8;



    #[Computed]
    public function total(): int
    {
        $listId = $this->filters['list'] ? [$this->filters['list']] : array_keys($this->list);

        return Sku::query()
            ->whereHas(
                'myResellerLists',
                fn ($q) => $q->whereIn('reseller_list_id', $listId)
            )->count();
    }


    #[Computed()]
    public function skus()
    {
        $listId = $this->filters['list'] ? [$this->filters['list']] : array_keys($this->list);

        return Sku::query()
            ->search($this->search)
            ->whereHas(
                'myResellerLists',
                fn ($q) => $q->whereIn('reseller_list_id', $listId)
            )
            ->when($this->filters['cat'], fn ($q) => $q->whereRelation('product', 'category_id', '=', $this->filters['cat']))
            ->with(['media', 'product.category'])
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

    #[Computed(persist: true)]
    public function list()
    {
        $list = ResellerList::query()
            ->whereBelongsTo(auth()->user())
            ->withCount('skus');

        logger($list->get()->toArray());

        return $list
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
        $this->perPage += 8;
    }

    public function render()
    {

        return view('livewire.my-catalog', [
            'skus' => $this->skus
        ]);
    }
}
