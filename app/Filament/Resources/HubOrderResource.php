<?php

namespace App\Filament\Resources;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Events\OrderCancelled;
use App\Events\OrderDelivered;
use App\Events\OrderPartialDelivered;
use App\Filament\Resources\HubOrderResource\Pages;
use App\Jobs\AddParcelToSteadFast;
use App\Models\Business;
use App\Models\Order;
use App\Models\User;
use Closure;
use Filament\Actions\StaticAction;
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
                'reseller.business'
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
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('id', $search);
                    }),
                Tables\Columns\TextColumn::make('consignment_id')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('consignment_id', $search);
                    })
                    ->label('CN'),
                Tables\Columns\TextColumn::make('created_at')
                    ->since(),
                Tables\Columns\TextColumn::make('reseller')
                    ->getStateUsing(fn (Model $record) => $record
                        ->reseller
                        ->business
                        ->name),
                // Tables\Columns\TextColumn::make('reseller.name')
                //     ->label('Owner'),
                // Tables\Columns\TextColumn::make('reseller.mobile')
                //     ->label('Mobile'),
                Tables\Columns\TextColumn::make('total_payable'),
                Tables\Columns\TextColumn::make('cod')
                    ->label('Order COD'),
                Tables\Columns\TextColumn::make('collected_cod')
                    ->wrap()
                    ->label('Collected COD'),
                Tables\Columns\TextColumn::make('items_sum_quantity')
                    ->label('Total Items')
                    ->sum('items', 'quantity'),
                Tables\Columns\TextColumn::make('status')
                    ->sortable()
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->preload()
                    ->options(Business::query()->wholesaler()->pluck('name', 'id'))
                    ->query(
                        function (Builder $query, array $data): Builder {
                            $businessId = $data['value'];
                            logger($businessId);
                            return $query
                                ->when($businessId, function ($q) use ($businessId) {
                                    return $q->whereHas('items', function ($q) use ($businessId) {
                                        return $q->whereHas('wholesaler', function ($q) use ($businessId) {
                                            return $q->whereRelation('business', 'id', $businessId);
                                        });
                                    });
                                });
                        }
                    ),
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options(OrderStatus::array()),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->native(false)
                            ->closeOnDateSelection(),
                        Forms\Components\DatePicker::make('created_until')->native(false)
                            ->closeOnDateSelection(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('updated_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('updated_at', '<=', $date),
                            );
                    })
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
                            ->label('Reseller COD (taka)')
                            ->content(fn (Order $record) => $record->cod),
                        Forms\Components\Select::make('return_skus')
                            ->label('Return order items')
                            ->multiple()
                            ->options(
                                fn (Order $record) => $record->items
                                    ->filter(fn ($item) => $item->status == OrderItemStatus::DeliveredToHub)
                                    ->pluck('sku.name', 'sku_id')
                            )
                            ->visible(fn (Get $get) => $get('status') == OrderStatus::Partial_Delivered->value)
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('collected_cod')
                            ->numeric()
                            ->visible(fn (Get $get) => $get('status') != OrderStatus::Cancelled->value)
                            ->maxValue(fn (Get $get, Order $record) => $record->cod)
                            ->required(fn (Get $get) => $get('status') != OrderStatus::Cancelled->value)
                            ->rules([
                                function (Order $record, Get $get) {
                                    return function (string $attribute, $value, Closure $fail) use ($get, $record) {

                                        $halfOfCod = ($record->cod) / 2;
                                        $msg = 'The COD is not correct';

                                        if (($get('status') == OrderStatus::Delivered->value) && ($value < $halfOfCod)) {
                                            $fail($msg);
                                        }

                                        if (($get('status') == OrderStatus::Partial_Delivered->value)) {

                                            // $returnSum = $record->items()
                                            //     ->whereIn('sku_id', $get('return_skus'))
                                            //     ->get()
                                            //     ->sum('reseller_price');

                                            // $amount = $record->cod - $returnSum;

                                            // $msg = 'Value should be within ' . $record->courier_charge . ' to ' . $amount;

                                            // if ($amount == $record->courier_charge)
                                            //     $msg = 'Value should be ' . $amount;

                                            if (($value < 1) || ($value > $record->cod))
                                                $fail($msg);
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


                                    if ($order->status == OrderStatus::Delivered) {
                                        $order->items()->update([
                                            'status' => OrderItemStatus::Delivered->value
                                        ]);
                                        $order->refresh();
                                        OrderDelivered::dispatch($order);
                                    } else if ($order->status == OrderStatus::Partial_Delivered) {

                                        $returnItems = $order->items()
                                            ->whereIn('sku_id', $data['return_skus']);


                                        $returnItems->update([
                                            'status' => OrderItemStatus::Returned->value
                                        ]);

                                        //update others items as delivered
                                        $order->items()
                                            ->whereNotIn('sku_id', $data['return_skus'])
                                            ->update([
                                                'status' => OrderItemStatus::Delivered->value
                                            ]);

                                        $items = $returnItems->get();


                                        $items->each(
                                            function ($item)  use ($order) {

                                                //stock update
                                                $item->sku->increment('quantity', $item->quantity);

                                                //send notification to wholesaler
                                                User::sendMessage(
                                                    users: $item->wholesaler,
                                                    title: $item->returnedMessage(),
                                                    url: route('filament.app.resources.wholesaler.orders.index', ['tableSearch' => $order->id])
                                                );
                                            }
                                        );

                                        $order->refresh();
                                        OrderPartialDelivered::dispatch($order);
                                    } else if ($order->status == OrderStatus::Cancelled) {

                                        //marked order items cancelled
                                        $order->items()->update([
                                            'status' => OrderItemStatus::Returned->value
                                        ]);

                                        //update stock
                                        $order->items->each(fn ($item) => $item->sku->increment('quantity', $item->quantity));

                                        $order->refresh();

                                        OrderCancelled::dispatch($order);
                                    }


                                    Notification::make()
                                        ->title('Order delivery status added successfully')
                                        ->success()
                                        ->send();
                                });
                            } catch (\Throwable $th) {
                                logger($th);
                                Notification::make()
                                    ->title('Something went wrong')
                                    ->danger()
                                    ->send();
                            }
                        }
                    ),

                Tables\Actions\Action::make('track')
                    ->label('Track Order')
                    ->url(fn (Order $record) => $record->tracking_url)
                    ->visible(fn (Order $record) => $record->tracking_code)
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('send_to_courier')
                    ->label('Send to courier')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => !$record->consignment_id && ($record->status == OrderStatus::ProcessingForHandOverToCourier))
                    ->action(fn (Order $record) => $record->addToCourier($record)),
                Tables\Actions\Action::make('print_address')
                    ->icon('heroicon-o-printer')
                    ->iconButton()
                    ->url(fn (Order $record): string => route('order.print.label', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Model $record) => $record->status == OrderStatus::HandOveredToCourier),
                Tables\Actions\Action::make('items')
                    ->label('Products')
                    ->icon('heroicon-o-bars-4')
                    ->iconButton()
                    ->action(function (Order $record, array $data, array $arguments) {
                        DB::transaction(function () use ($record, $data) {
                            //add collector
                            $record->addCollector(auth()->user()->id);
                            $record->refresh();

                            $collection = $record->collections
                                ->filter(fn ($item) => $item->wholesaler_id == $data['wholesaler'])
                                ->first();
                            $record->deliverToCollector($collection);

                            //print label
                            if (isset($data['print'])) {
                                return redirect(route('order.print.label', ['order' => $record]));
                            }
                        });
                    })
                    ->modalActions(
                        fn (Tables\Actions\Action $action, Order $record): array => match ($record->status) {
                            OrderStatus::WaitingForHubCollection => [
                                $action->makeExtraModalAction('collect', arguments: ['collect' => true])
                                    ->color('primary'),
                            ],
                            default => []
                        }
                    )
                    ->modalSubmitAction(null)

                    ->form([

                        Forms\Components\Select::make('wholesaler')
                            ->label('Select Business')
                            ->live()
                            ->afterStateHydrated(
                                function (Order $record, \Filament\Forms\Set $set) {
                                    $wholesalers = $record->wholesalers(OrderItemStatus::Approved)
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
                                        'name' => $item->wholesaler->business->name,
                                        'id' => $item->wholesaler->id,
                                    ])
                                    ->pluck('name', 'id')

                            ),
                        // Forms\Components\Checkbox::make('print')
                        //     ->label('Print Level')
                        //     ->visible(
                        //         fn (Order $record) => ($record->wholesalers(OrderItemStatus::Approved)->count() == 1) && ($record->status == OrderStatus::WaitingForHubCollection)
                        //     )
                        //     ->default(1),
                    ])
                    ->modalHeading(fn (Model $record) => 'Products list for order # ' . $record->id)
                    ->modalContent(fn (Model $record, Tables\Actions\Action $action) => view('orders.items-status', [
                        'items' => $record->loadMissing('items.wholesaler')->items
                    ])),

                Tables\Actions\Action::make('collector')
                    ->icon('heroicon-o-user')
                    ->iconButton()
                    ->label('Assign Collector')
                    ->visible(
                        fn (Model $record) => 0 & $record->wholesalers()->count() > 1 &&
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
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $record->status == OrderStatus::HandOveredToCourier
            )
            ->bulkActions([
                Tables\Actions\ActionGroup::make([

                    Tables\Actions\BulkAction::make('bulk-print')
                        ->icon('heroicon-o-printer')
                        ->label('Bulk Print Invoice')
                        ->color('success')
                        ->deselectRecordsAfterCompletion()
                        ->action(
                            function (Collection $records) {
                                $record_ids = $records->pluck('id')->join(',');
                                return redirect(route('orders.print.invoice', ['orders' => $record_ids]));
                            }
                        ),

                    Tables\Actions\BulkAction::make('print_list_for_courier')
                        ->icon('heroicon-o-printer')
                        ->label('Order List for Courier')
                        ->deselectRecordsAfterCompletion()
                        ->color('success')
                        ->action(
                            function (Collection $records) {
                                $record_ids = $records->pluck('id')->join(',');
                                return redirect(route('orders.print.courier', ['orders' => $record_ids]));
                            }
                        ),

                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageHubOrders::route('/'),
        ];
    }
}
