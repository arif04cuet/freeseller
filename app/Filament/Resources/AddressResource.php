<?php

namespace App\Filament\Resources;

use App\Enum\AddressType;
use App\Filament\Resources\AddressResource\Pages;
use App\Filament\Resources\AddressResource\RelationManagers;
use App\Filament\Resources\AddressResource\RelationManagers\UsersRelationManager;
use App\Models\Address;
use Closure;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $navigationLabel = 'Hub / Addresses';
    protected static ?int $navigationSort = 3;


    public static function form(Form $form): Form
    {
        $types = AddressType::collection()->except([
            AddressType::Division->value,
            AddressType::District->value,
        ])->toArray();

        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options($types)
                    ->columnSpanFull()
                    ->reactive(),

                Forms\Components\Fieldset::make('Parent Location')
                    ->schema([
                        Forms\Components\Select::make('division')
                            ->options(Address::whereType(AddressType::Division->value)->pluck('name', 'id'))
                            ->reactive(),
                        Forms\Components\Select::make('district')
                            ->options(fn (Closure $get) => Address::query()
                                ->whereType(AddressType::District->value)
                                ->whereParentId($get('division'))
                                ->pluck('name', 'id'))
                            ->reactive(),

                        Forms\Components\Select::make('upazila')
                            ->options(fn (Closure $get) => Address::query()
                                ->whereType(AddressType::Upazila->value)
                                ->whereParentId($get('district'))
                                ->pluck('name', 'id'))
                            ->reactive()
                            ->visible(fn (Closure $get) => in_array($get('type'), [
                                AddressType::Union->value,
                                AddressType::Hub->value
                            ])),

                        Forms\Components\Select::make('union')
                            ->options(fn (Closure $get) => Address::query()
                                ->whereType(AddressType::Union->value)
                                ->whereParentId($get('upazila'))
                                ->pluck('name', 'id'))
                            ->reactive()
                            ->visible(fn (Closure $get) => in_array($get('type'), [
                                AddressType::Hub->value
                            ])),
                    ])->columns(4),


                Forms\Components\TextInput::make('name')
                    ->columnSpanFull()
                    ->label(fn (Closure $get) => AddressType::tryFrom($get('type'))?->name ? AddressType::tryFrom($get('type'))->name . ' Name' : 'Name')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name'),
                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn (Model $record) => $record->type->name)
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(AddressType::array())
                    ->default(AddressType::Hub->value)
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
            UsersRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAddresses::route('/'),
            'create' => Pages\CreateAddress::route('/create'),
            'edit' => Pages\EditAddress::route('/{record}/edit'),
        ];
    }

    protected static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->whereType(AddressType::Hub->value)->count();
    }
}
