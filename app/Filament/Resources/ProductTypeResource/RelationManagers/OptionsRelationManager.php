<?php

namespace App\Filament\Resources\ProductTypeResource\RelationManagers;

use App\Enum\OptionType;
use App\Enum\OptionValueType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('field_type'),
                Tables\Columns\TextColumn::make('field_for'),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->forProduct())
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
