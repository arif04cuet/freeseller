<?php

namespace App\Filament\Resources;

use App\Enum\SystemRole;
use App\Filament\Resources\ExploreProductsResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\SkusRelationManager;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ResellerList;
use App\Models\Sku;
use App\Models\User;
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'category' => fn ($q) => $q->select('id', 'name'),
                'productType' => fn ($q) => $q->select('id', 'name'),
                'media',
                'skus',
                'owner'
            ])
            ->latest();
    }
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
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(fn (Model $record) => view(
                                'products.gallery',
                                [
                                    'medias' => $record->getAllImages(),
                                ]
                            )),
                    )
                    ->conversion('thumb'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                \App\Tables\Columns\ProductPrice::make('price'),

                Tables\Columns\TextColumn::make('quantity')
                    ->badge()
                    ->label('Color wise quantity')
                    ->getStateUsing(
                        function (Model $record) {
                            return $record->skus->map(
                                function ($sku) {
                                    $color = array_slice(explode('-', $sku->name), -2, 2);
                                    return $color[0] . '-' . $color[1] . '-' . $sku->quantity;
                                }
                            )->toArray();
                        }
                    ),
                Tables\Columns\TextColumn::make('skus_sum_quantity')
                    ->label('Total')
                    ->sum('skus', 'quantity'),
                Tables\Columns\TextColumn::make('productType.name'),
                Tables\Columns\TextColumn::make('category.name'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('owner')
                    ->label('Business')
                    ->multiple()
                    ->preload()
                    ->relationship('owner', 'name', fn (Builder $query) => $query->with(['business', 'roles'])->wholesalers())
                    ->getOptionLabelFromRecordUsing(
                        function (Model $record) {
                            return  $record->business->name . '(' . $record->id_number . ')';
                        }

                    )->indicateUsing(function (array $data): ?string {

                        if (empty($data['values'])) {
                            return null;
                        }
                        $businesses = User::with('business')->whereIn('id', $data['values'])
                            ->get()
                            ->map(fn ($user) => [
                                'id' => $user->id,
                                'name' => $user->business->name . '(' . $user->id_number . ')',
                            ])
                            ->pluck('name', 'id')
                            ->implode(', ');
                        return 'Business :' . $businesses;
                    }),

                Tables\Filters\SelectFilter::make('category')
                    ->searchable()
                    ->preload()
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('color')
                    ->searchable()
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
                    ->columns([
                        'default' => 2,
                        'md' => 4
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('productType.name'),
                        Infolists\Components\TextEntry::make('category.name'),
                        Infolists\Components\ViewEntry::make('price')
                            ->label('Price')
                            ->view('tables.columns.product-price'),
                        Infolists\Components\TextEntry::make('business')
                            ->label('Manufacturer')
                            ->html()
                            ->getStateUsing(
                                fn (Model $record) => '<a href="' . route('filament.app.resources.explore-products.index') . '?tableFilters[owner][values][0]=' . $record->owner->id . '"><u>' . $record->owner->business->name . '</u></a>'
                            ),
                        Infolists\Components\ImageEntry::make('focus_image')
                            ->columnSpan([
                                'default' => 2,
                                'md' => 1
                            ])
                            ->defaultImageUrl(
                                fn (Model $record) => $record->getMedia('sharees')->first()?->getUrl('thumb')
                            ),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpan([
                                'default' => 'full',
                                'md' => 3
                            ])
                            ->copyable()
                            ->copyableState(
                                fn ($state) => strip_tags($state)
                            )
                            ->copyMessage('description copied')
                            ->copyMessageDuration(1500)
                            ->tooltip('Click to copy text')
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
