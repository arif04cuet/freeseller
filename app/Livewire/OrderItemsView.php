<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderItemsView extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public array $itemIds;

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->query(OrderItem::query()->whereIn('id', $this->itemIds))
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->action(
                        Tables\Actions\Action::make('View Image')
                            ->action(function (Model $record): void {
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(fn (Model $record) => view(
                                'products.gallery',
                                [
                                    'medias' => $record->sku->getMedia('sharees'),
                                ]
                            )),
                    )
                    ->defaultImageUrl(
                        fn (Model $record) => $record->sku->getMedia('sharees')->first()->getUrl('thumb')
                    ),
                Tables\Columns\TextColumn::make('sku.name')
                    ->label('Product Name')
                    ->html()
                    ->formatStateUsing(fn (string $state) => '<u>' . $state . '</u>')
                    ->action(
                        Tables\Actions\Action::make('View Details')
                            ->label('')
                            ->action(function (Model $record): void {
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalHeading('Product Details')
                            ->modalContent(fn (Model $record) => view(
                                'products.single-sku',
                                [
                                    'sku' => $record->sku,
                                ]
                            )),
                    ),
                Tables\Columns\TextColumn::make('sku.product.name'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('order.note_for_wholesaler')
                    ->label('Reseller Note')
                    ->wrap()
                    ->visible(fn () => auth()->user()->isWholesaler()),
                Tables\Columns\TextColumn::make('wholesaler')
                    ->html()
                    ->visible(fn () => auth()->user()->isHubManager())
                    ->formatStateUsing(
                        function (Model $record) {

                            $wholesaler = $record->wholesaler;

                            return '<a href="tel:' . $wholesaler->mobile . '">' .
                                $wholesaler->business->name . '<br/>' .
                                $wholesaler->name .
                                '</a>';
                        }
                    )
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.order-items-view');
    }
}
