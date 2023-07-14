<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                TextInput::make('email'),
                TextInput::make('mobile'),

                TextInput::make('business.name')
                    ->label('Business Name')
                    ->afterStateHydrated(function (TextInput $component, $state, ?Model $record) {
                        $business = $record->business->first();
                        $component->state($business?->name);
                    }),

                TextInput::make('business.address')
                    ->label('Business Address')
                    ->afterStateHydrated(function (TextInput $component, $state, ?Model $record) {
                        $business = $record->business->first();
                        $component->state($business?->address);
                    }),

                TextInput::make('business.estd_year')
                    ->label('Business Estd Year')
                    ->afterStateHydrated(function (TextInput $component, $state, ?Model $record) {
                        $business = $record->business->first();
                        $component->state($business?->estd_year);
                    }),
                TextInput::make('business.type')
                    ->label('Business Type')
                    ->afterStateHydrated(function (TextInput $component, $state, ?Model $record) {
                        $business = $record->business->first();
                        $component->state($business?->type);
                    })
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('mobile')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('roles')
                    ->getStateUsing(function (Model $record) {
                        return $record->roles()->pluck('name')->implode(',');
                    }),
                TextColumn::make('created_at')->datetime(),
                ToggleColumn::make('is_active')
            ])
            ->filters([

                TernaryFilter::make('is_active'),
                SelectFilter::make('roles')
                    ->label('User Type')
                    ->relationship('roles', 'name')

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
