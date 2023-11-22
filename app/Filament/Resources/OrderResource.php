<?php

namespace App\Filament\Resources;

use App\Enum\AddressType;
use App\Enum\Courier;
use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\Widgets\OrderInstruction;
use App\Http\Integrations\Pathao\Requests\GetAreasRequest;
use App\Http\Integrations\Pathao\Requests\GetCitiesRequest;
use App\Http\Integrations\Pathao\Requests\GetZonesRequest;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ResellerList;
use App\Models\Sku;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification as NotificationsNotification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Notification;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationGroup = 'Reseller';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'My Orders';


    public static function getWidgets(): array
    {
        return [
            OrderInstruction::class
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([
            SystemRole::Reseller->value,
            'super_admin',
        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('items')->mine()->latest();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->columns([
                        'default' => 2,
                        'xl' => 2
                    ])
                    ->schema([

                        Forms\Components\Select::make('list')
                            ->options(auth()->user()->lists->pluck('name', 'id'))
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('hub_id')
                            ->label('Select Hub')
                            ->required()
                            ->visible(fn (\Filament\Forms\Get $get) => !empty($get('list')))
                            //->options(fn (\Filament\Forms\Get $get) => ResellerList::hubsInList($get('list')))
                            ->options(Address::query()->hubs()->pluck('name', 'id'))
                            ->default(fn () => Address::query()->hubs()->first()->id)
                            ->disabledOn('edit')
                            ->reactive(),
                    ]),
                Repeater::make('items')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 3,
                        'md' => 6
                    ])
                    ->required()
                    ->cloneable()
                    ->live()
                    ->visible(fn (\Filament\Forms\Get $get) => $get('hub_id') && $get('list'))
                    ->disableItemCreation(
                        fn (?Model $record, $context) => $context == 'edit' &&
                            $record?->status?->value != OrderStatus::WaitingForWholesalerApproval->value
                    )
                    ->schema([

                        Forms\Components\Select::make('sku')
                            ->label('Product')
                            ->visible(fn (\Filament\Forms\Get $get) => $get('../../hub_id') && $get('../../list'))
                            ->reactive()
                            ->required()
                            ->columnSpan([
                                'default' => 'full',
                                'md' => 2
                            ])
                            ->searchable()
                            ->getSearchResultsUsing(
                                fn (string $search) => Sku::query()
                                    ->where('id', $search)
                                    ->where('quantity', '>', 0)
                                    ->pluck('name', 'id')
                            )
                            ->getOptionLabelUsing(
                                function ($value): ?string {
                                    $sku = Sku::find($value);
                                    return  '<div class="flex gap-2">
                            <img src="' . $sku->getMedia('*')->first()->getUrl('thumb') . '"/>
                            <span>' . $sku->name . '</span>
                        </div>';
                                }
                            )
                            ->options(
                                function (Get $get) {

                                    $items = collect($get('../../items'))->pluck('sku')
                                        ->filter()
                                        ->toArray();

                                    $skus = ResellerList::find($get('../../list'))
                                        ->skus
                                        ->filter(fn ($sku) => $sku->quantity && !in_array($sku->id, $items))
                                        ->map(fn ($sku) => [
                                            'id' => $sku->id,
                                            'name' => '<div class="flex gap-2">
                                                <img src="' . $sku->getMedia('*')->first()?->getUrl('thumb') . '"/>
                                                <span>' . $sku->name . '</span>
                                            </div>'
                                        ])
                                        ->pluck('name', 'id');

                                    return $skus;
                                }
                            )
                            ->preload()
                            ->allowHtml(),
                        //->getOptionLabelUsing(fn ($value): ?string => '<span class="text-blue-500">Tailwind</span>'),
                        // Forms\Components\Placeholder::make('image')
                        //     ->visible(fn (\Filament\Forms\Get $get) => $get('sku'))
                        //     ->visibleFrom('md')
                        //     ->content(
                        //         function (Get $get) {
                        //             $media = Sku::find($get('sku'))->getMedia('sharees')->first();

                        //             return $media ?
                        //                 (new HtmlString('<img src="' . $media->getUrl('thumb') . '" / >')) :
                        //                 '';
                        //         }
                        //     ),

                        Forms\Components\TextInput::make('quantity')
                            ->reactive()
                            ->visible(fn (\Filament\Forms\Get $get) => $get('sku'))
                            ->helperText(fn (\Filament\Forms\Get $get) => 'Available quantity is ' . Sku::find($get('sku'))->quantity)
                            ->maxValue(fn (\Filament\Forms\Get $get) => Sku::find($get('sku'))->quantity)
                            ->required()
                            ->afterStateUpdated(function (Get $get, \Filament\Forms\Set $set, ?string $state) {

                                $subtotal = (int) $get('reseller_price') * (int) $state;
                                $set('subtotal', $subtotal);
                            })
                            ->numeric(),

                        Forms\Components\TextInput::make('reseller_price')
                            ->label('Sell Price')
                            ->live(debounce: 1000)
                            ->visible(fn (\Filament\Forms\Get $get) => $get('sku'))
                            ->helperText(fn (\Filament\Forms\Get $get) => 'Wholesaler Price is ' . (int) Sku::find($get('sku'))->price)
                            ->minValue(fn (\Filament\Forms\Get $get) => Sku::find($get('sku'))->price)
                            ->required()
                            ->afterStateUpdated(function (Get $get, \Filament\Forms\Set $set, ?string $state) {

                                $subtotal = (int) $get('quantity') * (int) $state;
                                $set('subtotal', $subtotal);
                            })
                            ->numeric(),

                        Forms\Components\TextInput::make('subtotal')
                            ->visible(fn (\Filament\Forms\Get $get) => $get('sku'))
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->visible(fn ($state) => $state)
                            ->disabled(),

                    ]),

                Forms\Components\Grid::make('total')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 3,
                        'xl' => 5
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('courier_charge')
                            ->required()
                            ->label('Courier charge')
                            ->disabled()
                            ->helperText(function (Get $get, \Filament\Forms\Set $set, ?Model $record, $state) {

                                $charge = Order::courierCharge($get('items'), $get('customer_id'));

                                $set('courier_charge', $charge);
                                $set('packaging_charge', Order::packgingCost());

                                return '';
                            }),

                        Forms\Components\TextInput::make('packaging_charge')
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('total_payable')
                            ->required()
                            ->disabled()
                            ->helperText(function (Get $get, \Filament\Forms\Set $set, ?Model $record, $state) {

                                $total_payable = Order::totalPayable($get('items'), $get('customer_id'));

                                $set('total_payable', $total_payable);

                                return '';
                            }),
                        Forms\Components\TextInput::make('total_saleable')
                            ->required()
                            ->disabled()
                            ->helperText(function (Get $get, \Filament\Forms\Set $set, ?Model $record, $state) {

                                $subtotals = Order::totalSubtotals($get('items'));
                                $set('total_saleable', $subtotals);
                                $payable = Order::totalPayable($get('items'), $get('customer_id'));

                                //profit
                                $cod = (int) $get('cod') ?? $subtotals;
                                $profit = $cod - $payable;
                                $set('profit', $profit);

                                return '';
                            }),

                        Forms\Components\TextInput::make('profit')
                            ->label('Your Profit')
                            ->disabled(),
                    ]),

                Forms\Components\TextInput::make('cod_update')
                    ->default(1)
                    ->disabled()
                    ->dehydrated(false)
                    ->hidden(),
                Forms\Components\TextInput::make('error')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->hidden(),
                Forms\Components\Select::make('customer_id')
                    ->label('Customer')
                    ->live()
                    ->required()
                    ->searchable()
                    ->searchPrompt('Search by Mobile')
                    ->getOptionLabelUsing(
                        function ($value): ?string {
                            $customer = Customer::find($value);
                            return $customer->name . '-' . $customer->mobile . ' - ' . $customer->address;
                        }
                    )
                    ->getSearchResultsUsing(
                        fn (string $search) => Customer::query()
                            ->select(
                                DB::raw("CONCAT(customers.name,'-',customers.mobile,'- ',customers.address) as label"),
                                'id'
                            )
                            ->mine()
                            ->where(
                                fn ($query) => $query->where('name', 'like', "{$search}%")
                                    ->orWhere('mobile', 'like', "{$search}%")
                            )
                            ->pluck('label', 'id')
                    )
                    ->createOptionAction(
                        fn ($action, \Filament\Forms\Set $set) => $action->action(
                            function ($data) use ($set) {
                                $data['courier'] = config('freeseller.default_courier');
                                $customer = Customer::updateOrCreate(
                                    ['mobile' => $data['mobile']],
                                    $data
                                );
                                $customer->resellers()->syncWithoutDetaching([auth()->user()->id]);
                                $set('customer_id', $customer->id);
                            }
                        )
                    )
                    ->createOptionForm([
                        Forms\Components\Grid::make('customer')
                            ->columns(3)
                            ->schema([

                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('mobile')
                                    ->label('Mobile')
                                    ->type('number')
                                    ->rules('numeric|digits_between:11,11')
                                    ->placeholder('01xxxxxxxxx')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->email(),
                                Forms\Components\Fieldset::make('Address')
                                    ->columns(3)
                                    ->schema([

                                        Forms\Components\Select::make('district_id')
                                            ->label('District')
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set) => $set('upazila_id', []))
                                            ->options(
                                                function () {
                                                    if (config('freeseller.default_courier') == Courier::Pathao->value) {

                                                        if ($cacheList = cache('pathao_district_list'))
                                                            return $cacheList;

                                                        $request = new GetCitiesRequest();
                                                        $response = $request->send();
                                                        $items = collect($response->json('data.data'))
                                                            ->map(fn ($item) => [
                                                                'id' => $item['city_id'],
                                                                'name' => $item['city_name'],
                                                            ])
                                                            ->pluck('name', 'id');

                                                        cache(
                                                            [
                                                                'pathao_district_list' => $items->toArray()
                                                            ]
                                                        );

                                                        return $items;
                                                    } else {
                                                        return Address::query()
                                                            ->where('type', AddressType::District->value)
                                                            ->pluck('name', 'id');
                                                    }
                                                }

                                            ),
                                        Forms\Components\Select::make('upazila_id')
                                            ->label('Upazila')
                                            ->required()
                                            ->searchable()
                                            ->options(

                                                function (Get $get) {

                                                    if (!$get('district_id'))
                                                        return [];

                                                    if (config('freeseller.default_courier') == Courier::Pathao->value) {

                                                        $cacheKey = 'pathao_district_' . $get('district_id');

                                                        if ($cacheList = cache($cacheKey))
                                                            return $cacheList;


                                                        $request = new GetZonesRequest($get('district_id'));
                                                        $response = $request->send();
                                                        $items = collect($response->json('data.data'))
                                                            ->map(fn ($item) => [
                                                                'id' => $item['zone_id'],
                                                                'name' => $item['zone_name'],
                                                            ])
                                                            ->pluck('name', 'id');

                                                        cache(
                                                            [
                                                                $cacheKey => $items->toArray()
                                                            ]
                                                        );

                                                        return $items;
                                                    } else {
                                                        return Address::query()
                                                            ->where('type', AddressType::Upazila->value)
                                                            ->where('parent_id', $get('district_id'))
                                                            ->pluck('name', 'id');
                                                    }
                                                }

                                            ),
                                        Forms\Components\Select::make('area_id')
                                            ->label('Area')
                                            ->required()
                                            ->searchable()
                                            ->visible(fn () => config('freeseller.default_courier') == Courier::Pathao->value)
                                            ->options(

                                                function (Get $get) {

                                                    if (!$get('upazila_id'))
                                                        return [];

                                                    if (config('freeseller.default_courier') == Courier::Pathao->value) {

                                                        $cacheKey = 'pathao_upazila_' . $get('upazila_id');

                                                        if ($cacheList = cache($cacheKey))
                                                            return $cacheList;



                                                        $request = new GetAreasRequest($get('upazila_id'));
                                                        $response = $request->send();
                                                        $items = collect($response->json('data.data'))
                                                            ->map(fn ($item) => [
                                                                'id' => $item['area_id'],
                                                                'name' => $item['area_name'],
                                                            ])
                                                            ->pluck('name', 'id');

                                                        cache(
                                                            [
                                                                $cacheKey => $items->toArray()
                                                            ]
                                                        );

                                                        return $items;
                                                    }
                                                }

                                            ),
                                        Forms\Components\Textarea::make('address')
                                            ->required(),
                                    ])

                            ]),
                    ]),

                Forms\Components\TextInput::make('cod')
                    //->default(1)
                    ->label('COD')
                    ->required()
                    ->helperText('total saleable + courier')
                    ->live(debounce: 1000)
                    ->numeric()
                    ->afterStateUpdated(function (\Filament\Forms\Set $set, Get $get, $state) {

                        $set('cod_update', 2);
                    })
                    ->hint(function (Get $get, $state, \Filament\Forms\Set $set, string $context) {

                        $state = (int) $state;
                        $subtotals = Order::totalSubtotals($get('items'));
                        $courierCharge = Order::courierCharge($get('items'), $get('customer_id'));
                        $cod = $subtotals + $courierCharge;

                        if ($get('cod_update') == 1) {
                            //$set('cod', $cod);
                        }

                        $lockAmount = (int) auth()->user()->lockAmount->sum('amount');
                        $balance = auth()->user()->balanceFloat - $lockAmount;

                        $amount = ($balance + $state) - Order::totalPayable($get('items'), $get('customer_id'));

                        if ($get('cod_update') != 1) {

                            // temporary solution.
                            if (config('freeseller.minimum_acount_balance') > 0) {
                                if ($amount < 0) {
                                    $set('error', 1);
                                    return 'Please recharge your wallet with TK = ' . abs($amount);
                                } elseif ($state > $cod) {
                                    $set('error', 1);

                                    return 'COD can\'t be grater than ' . $cod;
                                }
                            }
                        }
                        $set('error', 0);

                        return '';
                    })
                    ->hintColor('danger'),

                Forms\Components\Textarea::make('note_for_wholesaler')
                    ->label('Message for manufacturer if any')
                    ->maxLength(300)
                    ->rules('max:300')
                    ->hint(fn ($state, $component) => 'Max ' . $component->getMaxLength() . ' characters'),
                Forms\Components\Textarea::make('note_for_courier')
                    ->label('Message for Courier if any')
                    ->maxLength(300)
                    ->rules('max:300')
                    ->hint(fn ($state, $component) => 'Max ' . $component->getMaxLength() . ' characters'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn (Model $record) => match ($record->status) {
                    OrderStatus::WaitingForWholesalerApproval => route('filament.app.resources.orders.edit', ['record' => $record]),
                    default => null,
                },
            )
            ->defaultGroup(
                Tables\Grouping\Group::make('created_at')
                    ->date()
                    ->collapsible()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->grow(false)
                    ->searchable()
                    ->label('Order#')
                    ->formatStateUsing(fn ($state) => '<u>' . $state . '</u>')
                    ->html()
                    ->action(
                        Tables\Actions\Action::make('items')
                            ->label('Products')
                            ->icon('heroicon-o-bars-4')
                            ->iconButton()
                            ->action(function (Order $record, array $data, array $arguments) {
                            })
                            ->modalCancelAction(false)
                            ->modalSubmitAction(false)
                            ->modalHeading('Products details')
                            ->modalContent(fn (Model $record) => view('orders.items-status', [
                                'items' => $record->loadMissing('items.wholesaler')->items,
                            ])),
                    ),
                Tables\Columns\TextColumn::make('consignment_id')
                    ->label('CN'),
                Tables\Columns\TextColumn::make('created_at')->date(),
                Tables\Columns\TextColumn::make('items_sum_quantity')
                    ->label('Products')
                    ->sum('items', 'quantity'),

                Tables\Columns\TextColumn::make('total_payable')
                    ->label('Payable'),
                // Tables\Columns\TextColumn::make('total_saleable')
                //     ->label('Sallable'),
                Tables\Columns\TextColumn::make('cod')
                    ->label('COD'),
                Tables\Columns\TextColumn::make('collected_cod')->label('C.COD'),
                Tables\Columns\TextColumn::make('profit'),
                //->getStateUsing()
                // ->summarize(
                //     Sum::make()
                //         ->label('Total Profit Earned')
                //         ->query(fn (QueryBuilder $query) => $query->whereIn('status', [
                //             OrderStatus::Delivered->value,
                //             OrderStatus::Partial_Delivered->value,
                //         ])),
                // ),
                // Tables\Columns\TextColumn::make('customer.name')
                //     ->searchable()
                //     ->label('Customer Name'),
                Tables\Columns\TextColumn::make('customer.mobile')
                    ->searchable()
                    ->formatStateUsing(fn (Order $record, $state) => '<a href="tel:' . $state . '"><u>' . $record->customer->name . '<br/>' . $state . '</u></a>')
                    ->html()
                    ->label('Customer'),
                // Tables\Columns\TextColumn::make('customer.address')->label('Address'),
                // Tables\Columns\TextColumn::make('note')
                //     ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),

            ])

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options(OrderStatus::array()),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([

                    Tables\Actions\Action::make('cancel')
                        ->visible(fn (Model $record) => $record?->status == OrderStatus::WaitingForWholesalerApproval)
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-m-trash')
                        ->form([
                            forms\Components\Textarea::make('cancel_note')
                                ->required()
                        ])
                        ->action(
                            function (Model $record, array $data, $action) {

                                $record->update([
                                    'status' => OrderStatus::Cancelled->value,
                                    'cancelled_note' => $data['cancel_note'],
                                    'cancelled_by' => auth()->user()->id
                                ]);
                                $record->items()->update([
                                    'status' => OrderItemStatus::Cancelled->value
                                ]);

                                NotificationsNotification::make()
                                    ->title('Order cancelled successfully')
                                    ->send();
                            }
                        ),

                    Tables\Actions\Action::make('transactions')
                        ->modalCancelAction(false)
                        ->modalSubmitAction(false)
                        ->visible(fn (Order $record) => $record->delivered_at)
                        ->modalHeading(fn (Order $record) => 'Transactions for order#' . $record->id)
                        ->modalContent(fn (Model $record) => view('order.transactions', [
                            'order' => $record,
                        ])),
                    Tables\Actions\Action::make('track')
                        ->label('Track Order')
                        ->url(fn (Order $record) => $record->tracking_url)
                        ->visible(fn (Order $record) => $record->tracking_code)
                        ->openUrlInNewTab(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (?Model $record) => $record?->status?->value == OrderStatus::WaitingForWholesalerApproval->value),

                    Tables\Actions\Action::make('show_customer')
                        ->label('Customer')
                        ->color('success')
                        ->icon('heroicon-o-user')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalHeading(fn (Model $record) => $record->customer->name)
                        ->modalContent(fn (Model $record) => view('order.customer', ['order' => $record])),
                ])
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
