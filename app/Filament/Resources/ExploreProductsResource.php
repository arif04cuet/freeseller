<?php

namespace App\Filament\Resources;

use App\Enum\SystemRole;
use App\Filament\Resources\ExploreProductsResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\SkusRelationManager;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ResellerList;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class ExploreProductsResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Explore Products';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('product_type_id')
                            ->label('Product Type')
                            ->content(fn (Product $record): ?string => $record->productType->name),
                        Forms\Components\Placeholder::make('category')
                            ->label('Category')
                            ->content(fn (Product $record): ?string => $record->category->name),
                        Forms\Components\Placeholder::make('name')
                            ->content(fn (Product $record): ?string => $record->name),
                        Forms\Components\Placeholder::make('price')
                            ->content(fn (Product $record): ?string => $record->price),
                        Forms\Components\Placeholder::make('description')
                            ->columnSpanFull()
                            ->content(fn (Product $record): ?HtmlString => new HtmlString($record->description)),

                    ])
                    ->columns(4),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('sharees')
                    ->action(
                        Tables\Actions\Action::make('View Image')
                            ->action(function (Product $record): void {
                            })
                            ->modalActions([])
                            ->modalContent(fn (Model $record) => view(
                                'products.gallery',
                                [
                                    'medias' => $record->getAllImages(),
                                ]
                            )),
                    )
                    ->conversion('thumb'),
                Tables\Columns\TextColumn::make('productType.name'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                \App\Tables\Columns\ProductPrice::make('price'),

                Tables\Columns\TagsColumn::make('quantity')
                    ->label('Color wise quantity')
                    ->getStateUsing(
                        fn (Model $record) => $record->getQuantities()
                            ->map(fn ($item) => $item['color'] . ' = ' . $item['quantity'])
                            ->toArray()
                    ),
                Tables\Columns\TextColumn::make('skus_sum_quantity')
                    ->label('Total')
                    ->sum('skus', 'quantity'),
                Tables\Columns\TextColumn::make('category.name'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('owner')
                    ->label('Wholesaler')
                    ->relationship('owner', 'name', fn (Builder $query) => $query->wholesalers()),

                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('color')
                    ->options(AttributeValue::whereHas('attribute', function ($query) {
                        return $query->whereName('Color');
                    })->pluck('label', 'id'))
                    ->query(function (Builder $query, array $data) {
                        $query->when($data['value'], function ($q) use ($data) {
                            return $q->whereHas('skus', function ($q) use ($data) {
                                return $q->whereHas('attributeValues', function ($q) use ($data) {
                                    return $q->where('attribute_value_id', $data['value']);
                                });
                            });
                        });
                    }),

                Tables\Filters\Filter::make('price')
                    ->form([
                        Forms\Components\Grid::make('price_range')
                            ->columns(2)
                            ->schema([

                                Forms\Components\TextInput::make('from')->label('Price From')->numeric(),
                                Forms\Components\TextInput::make('to')->numeric(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $from): Builder => $query->where('price', '>=', $from),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $to): Builder => $query->where('price', '<=', $to),
                            );
                    }),
            ])
            ->actions([
                // Tables\Actions\Action::make('gallery')
                //     ->modalActions(fn (Model $record) => [])
                //     ->action(fn (Model $record) => dd($record->getAllImages()->toArray()))
                //     ->modalHeading(fn (Model $record) => 'All images of product - ' . $record->name)
                //     ->modalContent(fn (Model $record) => view('products.gallery', [
                //         'medias' => $record->getAllImages()
                //     ]))

            ])
            ->bulkActions([

                Tables\Actions\BulkAction::make('add_to_lilst')
                    ->visible(auth()->user()->hasRole(SystemRole::Reseller->value))
                    ->label('Add to List')
                    ->icon('heroicon-o-plus')
                    ->successNotificationTitle('Products added to specified list')
                    ->action(function (Tables\Actions\BulkAction $action, Collection $records, array $data): void {

                        ResellerList::find($data['list'])
                            ->products()
                            ->syncWithoutDetaching($records->pluck('id')->toArray());

                        $action->success();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->form([
                        Forms\Components\Select::make('list')
                            ->label('List')
                            ->options(auth()->user()->lists->pluck('name', 'id'))
                            ->required(),
                    ]),

            ])
            ->emptyStateActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('General')
                    ->heading(
                        fn (Model $record) => $record->name
                    )
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('productType.name'),
                        Infolists\Components\TextEntry::make('category.name'),
                        Infolists\Components\ViewEntry::make('price')
                            ->label('Price')
                            ->view('tables.columns.product-price'),
                        Infolists\Components\ImageEntry::make('focus_image')
                            ->defaultImageUrl(
                                fn (Model $record) => $record->getMedia('sharees')->first()->getUrl('thumb')
                            ),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpan(2)
                            ->html(),

                    ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            SkusRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExploreProducts::route('/'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }
}