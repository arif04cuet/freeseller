<?php

namespace App\Filament\Resources;

use App\Enum\SystemRole;
use App\Filament\Resources\ListResource\Pages;
use App\Filament\Resources\ListResource\RelationManagers;
use App\Filament\Resources\ListResource\RelationManagers\ProductsRelationManager;
use App\Models\List;
use App\Models\ResellerList;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class ListResource extends Resource
{
    protected static ?string $model = ResellerList::class;

    protected static ?string $navigationGroup = 'Reseller';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'My Lists';
    protected static ?string $slug = 'my-lists';
    protected static ?string $pluralModelLabel = 'List';


    // protected static function shouldRegisterNavigation(): bool
    // {
    //     return auth()->user()->hasAnyRole([SystemRole::Reseller->value]);
    // }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereBelongsTo(auth()->user());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->unique(modifyRuleUsing: function (Unique $rule, callable $get) {
                        return $rule
                            ->where('name', $get('name'))
                            ->where('user_id', auth()->user()->id);
                    }, ignoreRecord: true)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products', 'id'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()
            ])
            ->filters([
                //
            ])
            ->actions([])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLists::route('/'),
            'edit' => Pages\EditList::route('/{record}/edit')
        ];
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class
        ];
    }
}
