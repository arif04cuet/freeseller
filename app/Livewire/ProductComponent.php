<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Sku;
use App\Services\CartService;
use Cache;
use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Busket;
use Livewire\Attributes\Computed;

class ProductComponent extends Component
{
    public $productId;
    public int $quantity = 1;


    public ?string $selectedImg = null;
    public ?string $selectedThumb = null;

    public int $sku_id;

    function mount($product)
    {
        $this->productId = $product;
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

    public function render()
    {
        return view('livewire.product');
    }
}
