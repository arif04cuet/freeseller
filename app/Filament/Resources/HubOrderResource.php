<?php

namespace App\Filament\Resources;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Events\OrderDelivered;
use App\Filament\Resources\HubOrderResource\Pages;
use App\Http\Integrations\SteadFast\Requests\AddBulkParcelRequest;
use App\Jobs\AddParcelToSteadFast;
use App\Models\Order;
use App\Models\OrderCollection;
use App\Models\User;
use Closure;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
            ])->mine()->latest();
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
                Tables\Columns\TextColumn::make('cod')
                    ->label('COD'),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Total Items')
                    ->counts('items'),
                Tables\Columns\BadgeColumn::make('status')
                    ->sortable()
                    ->enum(OrderStatus::array())
                    ->colors([
                        'secondary' =>  OrderStatus::WaitingForWholesalerApproval->value,
                        'warning' =>  OrderStatus::Processing->value,
                        'success' => OrderStatus::Approved->value,
                        'danger' => OrderStatus::Cancelled->value,
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options(OrderStatus::array())
            ])
            ->actions([
                Tables\Actions\Action::make('mark_as_delivered')
                    ->label('Mark as Delivered?')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->color('success')
                    ->iconButton()
                    ->visible(fn (Order $record) => $record->tracking_code && $record->status != OrderStatus::Delivered)
                    ->action(
                        function (Order $record) {
                            try {

                                DB::transaction(function () use ($record) {
                                    $percentageFn = fn ($amount, $percentage): float => (float) (($percentage / 100) * $amount);

                                    //steadfast courier charge and cod 1 % deducted
                                    $amount = ($record->cod - $record->courier_charge);
                                    $dipositedAmount = $amount - $percentageFn($amount, 1);

                                    User::platformOwner()->deposit($dipositedAmount, ['description' => 'Order amount for order#' . $record->id]);

                                    OrderDelivered::dispatch($record);

                                    $record->update(['status' => OrderStatus::Delivered->value]);

                                    Notification::make()
                                        ->title('Order marked as delivered successfully')
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
                    ->icon('heroicon-o-arrow-circle-up')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => !$record->consignment_id && ($record->status == OrderStatus::ProcessingForHandOverToCourier))
                    ->action(fn (Order $record) => AddParcelToSteadFast::dispatchSync($record)),
                Tables\Actions\Action::make('print_address')
                    ->icon('heroicon-o-printer')
                    ->iconButton()
                    ->url(fn (Order $record): string => route('order.print.label', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Model $record) => $record->status == OrderStatus::Courier_In_Review),
                Tables\Actions\Action::make('items')
                    ->label('Products')
                    ->icon('heroicon-o-view-list')
                    ->iconButton()
                    ->action(function (Order $record, array $data, array $arguments) {

                        //add collector
                        $record->addCollector(auth()->user()->id);
                        $record->refresh();

                        $collection = $record->collections->filter(fn ($item) => $item->wholesaler_id == $data['wholesaler'])->first();
                        $record->deliverToCollector($collection);

                        //print label
                        if (isset($data['print']))
                            return redirect(route('order.print.label', ['order' => $record]));
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
                                function (Order $record, Closure $set) {
                                    $wholesalers = $record->wholesalers()
                                        ->pluck('wholesaler_id');

                                    if ($wholesalers->count() == 1) {
                                        $set('wholesaler', $wholesalers->first());
                                    }
                                }
                            )
                            ->visible(fn (Closure $get, Order $record) => $record->status == OrderStatus::WaitingForHubCollection)
                            ->required(fn (Closure $get) => !$get('all'))
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
                        'items' => $record->loadMissing('items.wholesaler')->items
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
