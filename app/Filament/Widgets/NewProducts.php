<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Sku;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class NewProducts extends BaseWidget
{

    protected static ?int $sort = 9;
    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        $date = Carbon::today()->subDays(7);

        return $table
            ->defaultPaginationPageOption(5)
            ->query(
                Sku::query()
                    ->with('product')
                    ->where('created_at', '>=', $date)
                    ->latest()
            )
            ->columns([
                Tables\Columns\Layout\Split::make([

                    SpatieMediaLibraryImageColumn::make('image')
                        ->collection('sharees')
                        ->action(
                            Tables\Actions\Action::make('View Image')
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
                        ->conversion('thumb'),
                    Tables\Columns\TextColumn::make('name')
                        ->url(fn (Sku $record) => route('filament.app.resources.explore-products.view', ['record' => $record->product->id]))
                        ->searchable(),
                    Tables\Columns\TextColumn::make('product.price')
                        ->label('Price')
                        ->formatStateUsing(fn (Model $record, $state) => view('products.sku.price', ['product' => $record->product]))
                        ->html(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Uploaded at')
                        ->since()
                ])->from('md')
            ]);
    }
}
