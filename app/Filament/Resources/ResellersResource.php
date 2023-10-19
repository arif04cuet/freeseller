<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResellersResource\Pages;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class ResellersResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Resellers';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'Reseller';

    protected static ?string $pluralModelLabel = 'Resellers';

    protected static ?string $slug = 'resellers';

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->resellers()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                TextInput::make('email'),
                TextInput::make('mobile'),

                TextInput::make('business.url')
                    ->label('Business URL')
                    ->afterStateHydrated(function (TextInput $component, $state, ?Model $record) {
                        $business = $record->business;
                        $component->state($business?->url);
                    }),
                TextInput::make('business.name')
                    ->label('Business Name')
                    ->afterStateHydrated(function (TextInput $component, $state, ?Model $record) {
                        $business = $record->business;
                        $component->state($business?->name);
                    }),

                TextInput::make('address.address')
                    ->label('Business Address')
                    ->afterStateHydrated(function (TextInput $component, $state, ?Model $record) {
                        $component->state($record->address?->address);
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
                        $business = $record->business;
                        $component->state($business?->type);
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Business Information')
                    ->schema([
                        Infolists\Components\Grid::make()
                            ->columns(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('business.name')->label('Name'),
                                Infolists\Components\TextEntry::make('address.address')->label('Address'),
                                Infolists\Components\TextEntry::make('business.estd_year')->label('Estd. Year'),
                                Infolists\Components\TextEntry::make('business.type')->label('Type'),
                                Infolists\Components\TextEntry::make('business.url')
                                    ->label('Url')
                                    ->formatStateUsing(
                                        fn (Model $record) => $record->business->url ? '<a href="' . $record->business->url . '"><u>FB / Website</u></a>' : 'No website'
                                    )
                                    ->html()
                                    ->openUrlInNewTab()
                            ])

                    ]),
                Infolists\Components\Section::make('Owner Information')
                    ->schema([
                        Infolists\Components\Grid::make()
                            ->columns(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('name'),
                                Infolists\Components\TextEntry::make('email'),
                                Infolists\Components\TextEntry::make('mobile'),
                            ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('business.name'),
                TextColumn::make('business.url')
                    ->label('Business Url')
                    ->formatStateUsing(
                        fn (Model $record) => $record->business->url ? '<a href="' . $record->business->url . '"><u>FB / Website</u></a>' : 'No website'
                    )
                    ->html()
                    ->openUrlInNewTab(),
                TextColumn::make('name')
                    ->label('Owner')
                    ->searchable(),
                TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('mobile')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TagsColumn::make('roles.name')
                    ->label('User Type'),
                TextColumn::make('created_at')->datetime(),
                ToggleColumn::make('is_active'),
            ])
            ->filters([

                TernaryFilter::make('is_active'),
            ])
            ->actions([
                Impersonate::make()
                    ->redirectTo(route('filament.app.pages.dashboard')),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListResellers::route('/'),
        ];
    }
}
