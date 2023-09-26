<?php

namespace App\Filament\Resources;

use App\Enum\SystemRole;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Resources\ProductResource\RelationManagers\SkusRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductType;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Wholesaler';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'My Products';


    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([
            SystemRole::Wholesaler->value,
            'super_admin'
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->mine();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }


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
                    ->label('Category')
                    ->preload()
                    ->searchable()
                    ->options(fn (\Filament\Forms\Get $get) => Category::query()
                        ->where('product_type_id', $get('product_type_id'))
                        ->pluck('name', 'id'))
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->visible(fn (\Filament\Forms\Get $get) => $get('product_type_id') && !ProductType::find($get('product_type_id'))?->is_varient_price)
                    ->required(fn (\Filament\Forms\Get $get) => $get('product_type_id') && !ProductType::find($get('product_type_id'))?->is_varient_price),

                Forms\Components\RichEditor::make('description')
                    ->required(),

                SpatieMediaLibraryFileUpload::make('image')
                    ->label('Product Focus Image')
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
                    ->action(
                        Tables\Actions\Action::make('View Image')
                            ->action(function (Product $record): void {
                            })
                            ->modalActions([])
                            ->modalContent(fn (Product $record) => view('products.single-image', ['url' => $record->getMedia('sharees')->first()->getUrl()])),
                    )
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
                    ->label('Total Stock')
                    ->sum('skus', 'quantity'),
                Tables\Columns\TextColumn::make('category.name'),
                Tables\Columns\TextColumn::make('owner.name'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
