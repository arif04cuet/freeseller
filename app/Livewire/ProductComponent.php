<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ResellerList;
use App\Models\Sku;
use App\Services\CartService;
use Cache;
use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Busket;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class ProductComponent extends Component
{
    public $productId;
    public int $quantity = 1;
    public int $stock = 0;

    public $listId = null;

    public ?string $selectedImg = null;
    public ?string $selectedThumb = null;

    public int $sku_id;

    function mount($product)
    {
        $this->productId = $product;
    }

    function updatedListId()
    {

        if (in_array($this->listId, array_keys($this->lists))) {

            ResellerList::find($this->listId)
                ->skus()
                ->syncWithoutDetaching([$this->sku_id]);

            $this->dispatch('success', message: "Product added to selected list successfully!");
        }
    }

    function updatedQuantity()
    {

        $this->quantity = $this->quantity > $this->stock ? $this->stock : $this->quantity;
    }

    #[Computed(persist: true)]
    public function lists()
    {
        $list = ResellerList::query()
            ->select(['id', 'name'])
            ->where('user_id', auth()->user()->id)
            ->pluck('name', 'id')
            ->toArray();

        return $list;
    }
    #[Computed(persist: true)]
    public function product()
    {
        return Product::with(['skus.media'])->find($this->productId);
    }

    public function selectedSku()
    {
        return $this->product
            ->skus
            ->filter(fn ($sku) => $sku->id == $this->sku_id)
            ->first();
    }

    public function loadImg($sku_id, $mediaId)
    {
        $this->sku_id = $sku_id;
        $sku = $this->selectedSku();
        $this->listId = $sku->loadMissing('myResellerLists:id')->myResellerLists?->first()?->id;
        $this->stock = $sku->quantity;
        $media = $sku->media->filter(fn ($media) => $media->id == $mediaId)->first();

        $this->selectedImg = $media->getUrl();
        $this->selectedThumb = $media->getUrl('thumb');
    }
    public function addToCart(): void
    {
        $sku = $this->selectedSku();
        if ($sku) {
            Busket::add($sku->id, $this->product->name, $sku->price, $this->quantity, ['image' => $this->selectedThumb]);
        }
    }

    #[On('showMessage')]
    public function render()
    {
        return view('livewire.product')
            ->title($this->product->name);
    }
}
