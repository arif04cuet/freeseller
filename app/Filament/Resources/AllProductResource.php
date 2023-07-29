<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllProductResource\Pages;
use App\Filament\Resources\AllProductResource\RelationManagers;
use App\Filament\Resources\ProductResource\RelationManagers\SkusRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductType;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AllProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Catalog';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'All Products';
    protected static ?string $slug = 'explore-products';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_type_id')
                    ->relationship('productType', 'name')
                    ->preload()
                    ->required()
                    ->reactive(),

                Forms\Components\Select::make('category_id')
                    ->options(fn (Closure $get) => Category::query()
                        ->where('product_type_id', $get('product_type_id'))
                        ->pluck('name', 'id'))
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->mask(
                        fn (Mask $mask) => $mask
                            ->numeric()
                            ->decimalPlaces(2)
                    )
                    ->visible(fn (Closure $get) => $get('product_type_id') && !ProductType::find($get('product_type_id'))?->is_varient_price)
                    ->required(fn (Closure $get) => $get('product_type_id') && !ProductType::find($get('product_type_id'))?->is_varient_price),

                Forms\Components\RichEditor::make('description')
                    ->required(),

                SpatieMediaLibraryFileUpload::make('image')
                    ->label('Product Main Image')
                    ->required()
                    ->enableReordering()
                    ->image()
                    ->enableDownload()
                    ->collection('sharees')




            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('sharees')
                    ->conversion('thumb'),
                Tables\Columns\TextColumn::make('productType.name'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->formatStateUsing(fn ($state) => (int) $state),
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

                Tables\Filters\Filter::make('price')
                    ->form([
                        Forms\Components\Grid::make('price_range')
                            ->columns(2)
                            ->schema([

                                Forms\Components\TextInput::make('from')->label('Price From')->numeric(),
                                Forms\Components\TextInput::make('to')->numeric(),
                            ])
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
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('gallery')
                    ->modalActions(fn (Model $record) => [])
                    ->action(fn (Model $record) => dd($record->getAllImages()->toArray()))
                    ->modalHeading(fn (Model $record) => 'All images of product - ' . $record->name)
                    ->modalContent(fn (Model $record) => view('products.gallery', compact('record')))

            ])
            ->bulkActions([]);
    }


    public static function getRelations(): array
    {
        return [
            SkusRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllProducts::route('/'),
            'view' => Pages\ViewProduct::route('/{record}')
        ];
    }
}
