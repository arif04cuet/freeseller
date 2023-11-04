<?php

namespace App\Livewire;

use App\Models\Sku;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ViewSku extends Component implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;

    public Sku $sku;

    public function skuInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->sku)
            ->schema([
                Infolists\Components\Section::make('General')
                    ->heading(
                        fn (Model $record) => $record->name
                    )
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('quantity')
                            ->label('Available qnt.'),
                        Infolists\Components\TextEntry::make('product.category.name'),
                        Infolists\Components\TextEntry::make('product.price')
                            ->label('Price')
                            ->html()
                            ->formatStateUsing(fn (Model $record) => view('tables.columns.product-price', [
                                'getRecord' => fn () => $record->product
                            ])->render()),

                        Infolists\Components\TextEntry::make('product.description')
                            ->label('Description')
                            ->columnSpan(2)
                            ->html(),
                        Infolists\Components\ImageEntry::make('images')
                            ->label('Images (click to large)')
                            ->action(
                                Infolists\Components\Actions\Action::make('View Image')
                                    ->action(function (Model $record): void {
                                    })
                                    ->modalSubmitAction(false)
                                    ->modalCancelAction(false)
                                    ->modalContent(fn (Model $record) => view(
                                        'products.gallery',
                                        [
                                            'medias' => $record->getMedia('sharees'),
                                        ]
                                    )),
                            )
                            ->defaultImageUrl(
                                fn (Model $record) => $record->getMedia('sharees')->first()->getUrl('thumb')
                            ),
                    ])
            ]);
    }

    public function render()
    {
        return view('livewire.view-sku');
    }
}
