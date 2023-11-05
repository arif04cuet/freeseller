<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SkusResource\Pages;
use App\Filament\Resources\SkusResource\RelationManagers;
use App\Models\Sku;
use App\Tables\Columns\QuantityUpdate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SkusResource extends Resource
{
    protected static ?string $model = Sku::class;

    protected static ?string $navigationGroup = 'Wholesaler';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'My Skus';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->isWholesaler();
    }


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereRelation('product', 'owner_id', auth()->user()->id)
            ->orderBy('quantity', 'asc');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\TextColumn::make('id'),
                    Tables\Columns\TextColumn::make('name'),
                    Tables\Columns\TextColumn::make('quantity')
                        ->formatStateUsing(fn ($state) => '<b>' . $state . '</b>' . ' pieces available')
                        ->html()
                        ->sortable(),
                    QuantityUpdate::make('update_quantity')
                ])->from('md'),

            ])
            ->filters([
                Tables\Filters\Filter::make('quantity')
                    ->label(fn () => "Low Stock ( quantity <" . Sku::lowStockThreshold() . ")")
                    ->default()
                    ->query(
                        fn (Builder $query): Builder => $query->where('quantity', '<', Sku::lowStockThreshold())
                    ),
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->preload()
                    ->searchable(),

            ], layout: FiltersLayout::AboveContent)
            ->actions([
                //Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                //Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSkuses::route('/')
        ];
    }
}
