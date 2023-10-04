<?php

namespace App\Filament\Resources;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Events\OrderCancelled;
use App\Events\OrderDelivered;
use App\Filament\Resources\HubOrderResource\Pages;
use App\Jobs\AddParcelToSteadFast;
use App\Models\Order;
use App\Models\User;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HubOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationGroup = 'Hub';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Hub Orders';

    protected static ?string $slug = 'hub/orders';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([
            SystemRole::HubManager->value,
            SystemRole::HubMember->value,
            'super_admin',
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'reseller',
            ])->mine()->latest();
    }

    public static function getNavigationBadge(): ?string
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
                        ->name),
                Tables\Columns\TextColumn::make('reseller.name')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('reseller.mobile')
                    ->label('Mobile'),
                Tables\Columns\TextColumn::make('total_saleable'),
                Tables\Columns\TextColumn::make('cod')
                    ->label('COD'),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Total Items')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('status')
                    ->sortable()
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options(OrderStatus::array()),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_as_delivered')
                    ->label('Mark as Delivered?')
                    ->icon('heroicon-o-check')
                    ->modalHeading('Add Delivery Status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options(OrderStatus::delivery_statuses())
                            ->default(OrderStatus::Delivered->value)
                            ->live()
                            ->required(),
                        Forms\Components\Placeholder::make('cod')
                            ->label('COD (taka)')
                            ->content(fn (Order $record) => $record->cod),
                        Forms\Components\TextInput::make('collected_cod')
                            ->numeric()
                            ->visible(fn (Get $get) => $get('status') != OrderStatus::Cancelled->value)
                            ->maxValue(fn (Get $get, Order $record) => $record->cod)
                            ->required(fn (Get $get) => $get('status') != OrderStatus::Cancelled->value)
                            ->rules([
                                function (Order $record, Get $get) {
                                    return function (string $attribute, $value, Closure $fail) use ($get, $record) {

                                        if (($get('status') == OrderStatus::Delivered->value) && ($value != $record->cod)) {
                                            $fail('The COD mismatched');
                                        }

                                        if (($get('status') == OrderStatus::Partial_Delivered->value) &&
                                            ($value < 1 || $value > $record->cod)
                                        ) {
                                            $fail('The :attribute is invalid.');
                                        }
                                    };
                                },
                            ]),
                    ])
                    ->color('success')
                    ->iconButton()
                    ->visible(fn (Order $record) => $record->tracking_code && $record->status == OrderStatus::HandOveredToCourier)
                    ->action(
                        function (Order $record, array $data) {

                            try {

                                DB::transaction(function () use ($record, $data) {
                                    $order = $record;
                                    $order->update([
                                        'status' => OrderStatus::from($data['status'])->value,
                                        'collected_cod' => isset($data['collected_cod']) ? $data['collected_cod'] : 0,
                                    ]);

                                    $order->refresh();

                                    if ($order->status == OrderStatus::Delivered) {
                                        OrderDelivered::dispatch($order);
                                    } else if ($order->status == OrderStatus::Partial_Delivered) {
                                    } else if ($order->status == OrderStatus::Cancelled) {
                                        OrderCancelled::dispatch($order);
                                    }


                                    Notification::make()
                                        ->title('Order delivery status added successfully')
                                        ->success()
                                        ->send();
                                });
                            } catch (\Throwable $th) {
                                Notification::make()
                                    ->title('Something went wrong')
                                    ->danger()
                                    ->send();
                            }
                        }
                    ),

                Tables\Actions\Action::make('track')
                    ->label('Track Order')
                    ->url(fn (Order $record) => 'https://steadfast.com.bd/t/' . $record->tracking_code)
                    ->visible(fn (Order $record) => $record->tracking_code)
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('send_to_courier')
                    ->label('Send to courier')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => !$record->consignment_id && ($record->status == OrderStatus::ProcessingForHandOverToCourier))
                    ->action(fn (Order $record) => AddParcelToSteadFast::dispatchSync($record)),
                Tables\Actions\Action::make('print_address')
                    ->icon('heroicon-o-printer')
                    ->iconButton()
                    ->url(fn (Order $record): string => route('order.print.label', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Model $record) => $record->status == OrderStatus::ProcessingForHandOverToCourier),
                Tables\Actions\Action::make('items')
                    ->label('Products')
                    ->icon('heroicon-o-bars-4')
                    ->iconButton()
                    ->action(function (Order $record, array $data, array $arguments) {

                        //add collector
                        $record->addCollector(auth()->user()->id);
                        $record->refresh();

                        $collection = $record->collections->filter(fn ($item) => $item->wholesaler_id == $data['wholesaler'])->first();
                        $record->deliverToCollector($collection);

                        //print label
                        if (isset($data['print'])) {
                            return redirect(route('order.print.label', ['order' => $record]));
                        }
                    })
                    ->modalActions(
                        fn (Tables\Actions\Action $action, Order $record): array => match ($record->status) {
                            OrderStatus::WaitingForHubCollection => [
                                $action->makeExtraModalAction('collect', arguments: ['collect' => true]),
                            ],
                            default => []
                        }
                    )
                    ->modalSubmitAction(null)

                    ->form([

                        Forms\Components\Select::make('wholesaler')
                            ->label('Select Wholesaler')
                            ->reactive()
                            ->afterStateHydrated(
                                function (Order $record, \Filament\Forms\Set $set) {
                                    $wholesalers = $record->wholesalers()
                                        ->pluck('id');
                                    if ($wholesalers->count() == 1) {
                                        $set('wholesaler', $wholesalers->first());
                                    }
                                }
                            )
                            ->visible(fn (\Filament\Forms\Get $get, Order $record) => $record->status == OrderStatus::WaitingForHubCollection)
                            ->required(fn (\Filament\Forms\Get $get) => !$get('all'))
                            ->options(
                                fn (Order $record) => $record->loadMissing('items.wholesaler')
                                    ->items
                                    ->filter(fn ($item) => $item->status == OrderItemStatus::Approved)
                                    ->map(fn ($item) => [
                                        'name' => $item->wholesaler->name,
                                        'id' => $item->wholesaler->id,
                                    ])
                                    ->pluck('name', 'id')

                            ),
                        Forms\Components\Checkbox::make('print')
                            ->label('Print Level')
                            ->visible(
                                fn (Order $record) => ($record->wholesalers()->count() == 1) && ($record->status == OrderStatus::WaitingForHubCollection)
                            )
                            ->default(1),
                    ])
                    ->modalHeading('Products details')
                    ->modalContent(fn (Model $record) => view('orders.items-status', [
                        'items' => $record->loadMissing('items.wholesaler')->items,
                    ])),

                Tables\Actions\Action::make('collector')
                    ->icon('heroicon-o-user')
                    ->iconButton()
                    ->label('Assign Collector')
                    ->visible(
                        fn (Model $record) => $record->wholesalers()->count() > 1 &&
                            ($record->status == OrderStatus::WaitingForHubCollection) &&
                            auth()->user()->isHubManager()
                    )
                    ->action(
                        fn (Order $record, array $data) => $data['self'] ?
                            $record->addCollector(auth()->user()->id) : $record->addCollector($data['collector_id'])
                    )
                    ->modalHeading(fn (Model $record) => 'Assign Collector to Order no ' . $record->id)
                    ->form([
                        Forms\Components\Checkbox::make('self')
                            ->label('Assign Myself')
                            ->reactive(),
                        Forms\Components\Select::make('collector_id')
                            ->label('Select Collector')
                            ->visible(fn (\Filament\Forms\Get $get) => !$get('self'))
                            ->required(fn (\Filament\Forms\Get $get) => !$get('self'))
                            ->options(
                                fn () => User::query()
                                    ->whereRelation('address', 'address_id', auth()->user()->address->address_id)
                                    ->role(SystemRole::HubMember->value)
                                    ->pluck('name', 'id')

                            ),
                    ]),

            ])
            ->bulkActions([
                // Tables\Actions\BulkAction::make('sent_to_courier')
                //     ->action(function (Collection $records) {
                //         dd(Order::addToCourier($records));
                //     })
                //     ->requiresConfirmation()
                //     ->deselectRecordsAfterCompletion()
                //     ->color('danger')
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageHubOrders::route('/'),
        ];
    }
}
