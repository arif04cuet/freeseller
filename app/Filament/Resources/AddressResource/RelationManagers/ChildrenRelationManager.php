<?php

namespace App\Filament\Resources\AddressResource\RelationManagers;

use App\Enum\AddressType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

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
            ->recordUrl(fn (Model $record) => route('filament.app.resources.addresses.edit', ['record' => $record]))
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (RelationManager $livewire, array $data): array {

                        $data['type']  = match ($livewire->getOwnerRecord()->type) {
                            AddressType::Division => AddressType::District->value,
                            AddressType::District => AddressType::Upazila->value,
                            AddressType::Upazila => AddressType::Union->value
                        };

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                //Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([]);
    }
}
