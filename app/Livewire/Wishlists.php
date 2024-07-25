<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Sku;
use App\Models\Wishlist;
use Bavix\Wallet\Models\Transaction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Busket;

class Wishlists extends Component
{

    #[Computed()]
    public function items()
    {
        return Sku::query()
            ->with(['media', 'product.category'])
            ->whereHas('wishlists', function ($q) {
                $q->whereBelongsTo(auth()->user(), 'reseller')
                    ->latest();
            })
            ->paginate(10);
    }

    public function addToCart($skuId): void
    {
        $sku = Sku::with(['product', 'media'])->find($skuId);
        $thumb = $sku->media->first()->getUrl('thumb');

        if ($sku) {
            Busket::add($sku->id, $sku->product->name, $sku->price, 1, ['image' => $thumb]);
        }
        auth()->user()->wishlists()->where(['sku_id' => $sku->id])->delete();
    }


    public function render(): View
    {
        return view('livewire.wishliasts');
    }
}
