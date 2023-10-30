<?php

namespace App\Filament\Resources;

use App\Enum\AddressType;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\Widgets\OrderInstruction;
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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

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
                            ->options(fn (\Filament\Forms\Get $get) => ResellerList::hubsInList($get('list')))
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
                                fn (string $search) => Sku::query()->where('id', $search)->pluck('name', 'id')
                            )
                            ->options(
                                function (Get $get) {

                                    $items = collect($get('../../items'))->pluck('sku')
                                        ->filter()
                                        ->toArray();

                                    $skus = ResellerList::find($get('../../list'))
                                        ->skus
                                        ->filter(fn ($sku) => !in_array($sku->id, $items))
                                        ->map(fn ($sku) => [
                                            'id' => $sku->id,
                                            'name' => '<div class="flex gap-2">
                                                <img src="' . $sku->getMedia('*')->first()->getUrl('thumb') . '"/>
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

                                $charge = Order::courierCharge($get('items'));

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

                                $total_payable = Order::totalPayable($get('items'));

                                $set('total_payable', $total_payable);

                                return '';
                            }),
                        Forms\Components\TextInput::make('total_saleable')
                            ->required()
                            ->disabled()
                            ->helperText(function (Get $get, \Filament\Forms\Set $set, ?Model $record, $state) {

                                $subtotals = Order::totalSubtotals($get('items'));
                                $totalWholesalerAmount = Order::totalWholesaleAmount($get('items'));
                                $set('total_saleable', $subtotals);
                                $payable = Order::totalPayable($get('items'));

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
                            ->whereRelation('resellers', 'reseller_id', auth()->user()->id)
                            ->select(
                                DB::raw("CONCAT(customers.name,'-',customers.mobile,'- ',customers.address) as label"),
                                'id'
                            )
                            ->where('name', 'like', "{$search}%")
                            ->orWhere('mobile', 'like', "{$search}%")
                            ->pluck('label', 'id')
                    )
                    ->createOptionAction(
                        fn ($action, \Filament\Forms\Set $set) => $action->action(
                            function ($data) use ($set) {
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
                                                Address::query()
                                                    ->where('type', AddressType::District->value)
                                                    ->pluck('name', 'id')
                                            ),
                                        Forms\Components\Select::make('upazila_id')
                                            ->label('Upazila')
                                            ->required()
                                            ->searchable()
                                            ->options(
                                                fn (Get $get) => !$get('district_id') ? [] : Address::query()
                                                    ->where('type', AddressType::Upazila->value)
                                                    ->where('parent_id', $get('district_id'))
                                                    ->pluck('name', 'id')
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
                        $courierCharge = Order::courierCharge($get('items'));
                        $cod = $subtotals + $courierCharge;

                        if ($get('cod_update') == 1) {
                            //$set('cod', $cod);
                        }

                        $lockAmount = (int) auth()->user()->lockAmount->sum('amount');
                        $balance = auth()->user()->balanceFloat - $lockAmount;

                        $amount = ($balance + $state) - Order::totalPayable($get('items'));

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
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
                Tables\Columns\TextColumn::make('items_sum_quantity')
                    ->label('Products')
                    ->sum('items', 'quantity'),
                Tables\Columns\TextColumn::make('total_payable')
                    ->label('Payable'),
                Tables\Columns\TextColumn::make('total_saleable')
                    ->label('Sallable'),
                Tables\Columns\TextColumn::make('cod')
                    ->label('Order COD'),
                Tables\Columns\TextColumn::make('collected_cod'),
                Tables\Columns\TextColumn::make('profit')
                    ->summarize(
                        Sum::make()
                            ->label('Total Profit Earned')
                            ->query(fn (QueryBuilder $query) => $query->where('status', [
                                OrderStatus::Delivered->value,
                                OrderStatus::Partial_Delivered->value,
                            ])),
                    ),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->label('Customer Name'),
                Tables\Columns\TextColumn::make('customer.mobile')->label('Mobile'),
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

                    Tables\Actions\Action::make('transactions')
                        ->modalCancelAction(false)
                        ->modalSubmitAction(false)
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
