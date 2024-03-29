<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WholesalerResource\Pages;
use App\Models\Address;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class WholesalerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Wholesalers';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Wholesaler';

    protected static ?string $pluralModelLabel = 'Wholesalers';

    protected static ?string $slug = 'wholesalers';

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()
            ->with(['wallet', 'roles', 'products']);
    }

    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getEloquentQuery()->wholesalers()->mine()->count();
    // }

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
                        $business = $record->business;
                        $component->state($business?->name);
                    }),

                TextInput::make('business.address')
                    ->label('Business Address')
                    ->afterStateHydrated(function (TextInput $component, $state, ?Model $record) {
                        $business = $record->business;
                        $component->state($business?->address);
                    }),

                TextInput::make('business.estd_year')
                    ->label('Business Estd Year')
                    ->afterStateHydrated(function (TextInput $component, $state, ?Model $record) {
                        $business = $record->business;
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
                TextColumn::make('id_number')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $baseNumber = config('freeseller.base_id_number');
                        return $query->whereRaw('CONCAT("W","", ? + id) = ?', [$baseNumber, $search]);
                    }),
                TextColumn::make('business.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(['name', 'email', 'mobile'])
                    ->html()
                    ->formatStateUsing(
                        fn (Model $record, $state) => $state . '<br/>' . $record->email . '<br/>' . $record->mobile
                    ),

                TextColumn::make('balance_float')
                    ->label('Balance'),
                TextColumn::make('products_count')
                    ->label('Product')
                    ->counts('products'),
                TextColumn::make('skus_sum_quantity')
                    ->label('Stock')
                    ->sum('skus', 'quantity'),

                TextColumn::make('created_at')->datetime(),
                ToggleColumn::make('is_active')
                    ->updateStateUsing(
                        function (User $record, $state) {
                            $record->toggleUser($state);
                        }
                    ),
            ])
            ->filters([

                TernaryFilter::make('is_active'),
                // SelectFilter::make('roles')
                //     ->label('User Type')
                //     ->relationship('roles', 'name')

            ])
            ->actions([
                Impersonate::make()
                    ->redirectTo(route('filament.app.pages.dashboard')),
                Tables\Actions\ViewAction::make()->iconButton(),
            ])
            ->bulkActions([
                //Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListWholesalers::route('/'),
        ];
    }
}
