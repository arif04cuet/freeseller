<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListResource\Pages;
use App\Filament\Resources\ListResource\RelationManagers\SkusRelationManager;
use App\Models\ResellerList;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereBelongsTo(auth()->user())
            ->withCount(['skus']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(modifyRuleUsing: function (Unique $rule, callable $get) {
                        return $rule
                            ->where('name', $get('name'))
                            ->where('user_id', auth()->user()->id);
                    }, ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('skus_count')
                    ->label('Total Products')
                    ->counts('skus', 'id'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([])
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => !$record->skus_count,
            )
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLists::route('/'),
            'edit' => Pages\EditList::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SkusRelationManager::class,
        ];
    }
}
