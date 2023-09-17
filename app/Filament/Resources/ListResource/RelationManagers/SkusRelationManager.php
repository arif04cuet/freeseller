<?php

namespace App\Filament\Resources\ListResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SkusRelationManager extends RelationManager
{
    protected static string $relationship = 'skus';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product.category'))
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('sharees')
                    ->action(
                        Tables\Actions\Action::make('View Image')
                            ->action(function (Model $record): void {
                            })
                            ->modalActions([])
                            ->modalContent(fn (Model $record) => view(
                                'products.gallery',
                                [
                                    'medias' => $record->getMedia("sharees")
                                ]
                            )),
                    )
                    ->conversion('thumb'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => (int) $state),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('product.category.name')

                // SpatieMediaLibraryImageColumn::make('image')
                //     ->collection('sharees')
                //     ->action(
                //         Tables\Actions\Action::make('View Image')
                //             ->action(function (Product $record): void {
                //             })
                //             ->modalActions([])
                //             ->modalContent(fn (Model $record) => view('products.gallery', compact('record'))),
                //     )
                //     ->conversion('thumb'),
                // // Tables\Columns\TextColumn::make('productType.name'),
                // Tables\Columns\TextColumn::make('name')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('product.price')
                //     ->formatStateUsing(fn ($state) => (int) $state),
                // // Tables\Columns\TagsColumn::make('quantity')
                // //     ->label('Color wise quantity')
                // //     ->getStateUsing(
                //         fn (Model $record) => $record->getQuantities()
                //             ->map(fn ($item) => $item['color'] . ' = ' . $item['quantity'])
                //             ->toArray()
                //     ),
                //,
                // Tables\Columns\TextColumn::make('skus_sum_quantity')
                //     ->label('Total')
                //     ->sum('skus', 'quantity'),
                //Tables\Columns\TextColumn::make('category.name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}
