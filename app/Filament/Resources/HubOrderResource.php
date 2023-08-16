<?php

namespace App\Filament\Resources;

use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\HubOrderResource\Pages;
use App\Models\Order;
use App\Models\User;
use Closure;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HubOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationGroup = 'Hub';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Orders';
    protected static ?string $slug = 'hub/orders';

    protected static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([
            SystemRole::HubManager->value,
            SystemRole::HubMember->value,
            'super_admin'
        ]);
    }


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'reseller'
            ])->mine();
    }

    protected static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getQueries(Builder $builder)
    {
        $addSlashes = str_replace('?', "'?'", $builder->toSql());
        return vsprintf(str_replace('?', '%s', $addSlashes), $builder->getBindings());
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
                Tables\Columns\TextColumn::make('id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->since(),
                Tables\Columns\TextColumn::make('reseller')
                    ->getStateUsing(fn (Model $record) => $record
                        ->reseller
                        ->business
                        ->first()
                        ->name),
                Tables\Columns\TextColumn::make('reseller.name')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('reseller.mobile')
                    ->label('Mobile'),
                Tables\Columns\TextColumn::make('total_saleable'),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Total Items')
                    ->counts('items'),
                Tables\Columns\BadgeColumn::make('status')
                    ->enum(OrderStatus::array())
                    ->colors([
                        'secondary' =>  OrderStatus::WaitingForWholesalerApproval->value,
                        'warning' =>  OrderStatus::Processing->value,
                        'success' => OrderStatus::Approved->value,
                        'danger' => OrderStatus::Cancelled->value,
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('print_address')
                        ->visible(fn (Model $record) => $record->status == OrderStatus::ProcessingForHandOverToCourier),
                    Tables\Actions\Action::make('sent_to_courier')
                        ->visible(fn (Model $record) => $record->status == OrderStatus::ProcessingForHandOverToCourier),
                ]),
                Tables\Actions\Action::make('items')
                    ->action(fn () => dd('ok'))
                    ->modalActions([])
                    ->modalHeading('Items details')
                    ->modalContent(fn (Model $record) => view('orders.items-status', [
                        'items' => $record->loadMissing('items.wholesaler')->items
                    ])),

                Tables\Actions\Action::make('collector')
                    ->icon('heroicon-o-user')
                    ->iconButton()
                    ->label('Assign Collector')
                    ->visible(
                        fn (Model $record) => ($record->status == OrderStatus::WaitingForHubCollection) && auth()->user()->isHubManager()
                    )
                    ->action(
                        fn (Model $record, array $data) => $data['self'] ?
                            $record->addCollector(auth()->user()->id) : $record->addCollector($data['collector_id'])
                    )
                    ->modalHeading(fn (Model $record) => 'Assign Collector to Order no ' . $record->id)
                    ->form([
                        Forms\Components\Checkbox::make('self')
                            ->label('Assign Myself')
                            ->reactive(),
                        Forms\Components\Select::make('collector_id')
                            ->label('Select Collector')
                            ->visible(fn (Closure $get) => !$get('self'))
                            ->required(fn (Closure $get) => !$get('self'))
                            ->options(
                                fn () => User::query()
                                    ->whereRelation('address', 'address_id', auth()->user()->address->address_id)
                                    ->role(SystemRole::HubMember->value)
                                    ->pluck('name', 'id')

                            )
                    ]),

            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageHubOrders::route('/'),
        ];
    }
}
