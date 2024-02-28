<?php

namespace App\Livewire;

use App\Models\Product as ModelsProduct;
use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Component
{
    public ModelsProduct $product;

    public ?string $selectedImg = null;

    function mount($product)
    {
        $this->product = $product
            ->loadMissing(['skus.media']);
    }

    public function loadImg($mediaId)
    {
        $this->selectedImg = Media::find($mediaId)->getUrl();
    }

    public function render()
    {
        return view('livewire.product');
    }
}
