<?php

namespace App\Filament\Resources;

use App\Enum\OptionType;
use App\Enum\OptionValueType;
use App\Filament\Resources\OptionResource\Pages;
use App\Filament\Resources\OptionResource\RelationManagers;
use App\Models\Option;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OptionResource extends Resource
{
    protected static ?string $model = Option::class;

    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('field_for')
                    ->options(OptionType::array())
                    ->required(),

                Forms\Components\Select::make('field_type')
                    ->options(OptionValueType::array())
                    ->required(),

                Forms\Components\TextInput::make('placeholder'),
                Forms\Components\TextInput::make('error_message'),
                Forms\Components\TextInput::make('length')->numeric(),
                Forms\Components\TextInput::make('min')->numeric(),
                Forms\Components\TextInput::make('max')->numeric(),
                Forms\Components\TextInput::make('sort_order')->numeric(),
                Forms\Components\Toggle::make('required')->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('field_for'),
                Tables\Columns\TextColumn::make('field_type'),
            ])
            ->filters([
                //
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOptions::route('/'),
            'create' => Pages\CreateOption::route('/create'),
            'edit' => Pages\EditOption::route('/{record}/edit'),
        ];
    }
}
