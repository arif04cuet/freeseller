<?php

namespace App\Filament\Resources;

use App\Enum\AddressType;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Address;
use App\Models\AttributeValue;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ResellerList;
use App\Models\Sku;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component as Livewire;
use Illuminate\Support\Str;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationGroup = 'Reseller';
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'My Orders';


    protected static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([
            SystemRole::Reseller->value,
            'super_admin'
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
                Forms\Components\Select::make('list')
                    ->options(auth()->user()->lists->pluck('name', 'id'))
                    ->reactive(),

                Forms\Components\Select::make('hub_id')
                    ->label('Select Hub')
                    ->required()
                    ->visible(fn (Closure $get) => !empty($get('list')))
                    ->options(fn (Closure $get) => ResellerList::hubsInList($get('list')))
                    ->disabledOn('edit')
                    ->reactive(),

                Repeater::make('items')
                    ->columnSpanFull()
                    ->columns(5)
                    ->cloneable()
                    ->reactive()
                    ->disableItemCreation(fn (?Model $record) => $record?->status?->value != OrderStatus::WaitingForWholesalerApproval->value)
                    ->schema([

                        Forms\Components\Select::make('sku')
                            ->label('Product')
                            ->visible(fn (Closure $get) => $get('../../hub_id') && $get('../../list'))
                            ->reactive()
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(
                                fn (string $search) => Sku::query()->where('id', $search)->pluck('name', 'id')
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => Sku::find($value)?->name),


                        Forms\Components\TextInput::make('quantity')
                            ->reactive()
                            ->visible(fn (Closure $get) => $get('sku'))
                            ->helperText(fn (Closure $get) => 'Available quantity is ' . Sku::find($get('sku'))->quantity)
                            ->maxValue(fn (Closure $get) => Sku::find($get('sku'))->quantity)
                            ->required()
                            ->afterStateUpdated(function (Closure $get, Closure $set, ?string $state) {

                                $subtotal = (int) $get('reseller_price') * (int) $state;
                                $set('subtotal', $subtotal);
                            })
                            ->numeric(),

                        Forms\Components\TextInput::make('reseller_price')
                            ->label('Sell Price')
                            ->reactive()
                            ->visible(fn (Closure $get) => $get('sku'))
                            ->helperText(fn (Closure $get) => 'Wholesaler Price is ' . (int) Sku::find($get('sku'))->price)
                            ->minValue(fn (Closure $get) => Sku::find($get('sku'))->wholesalePrice)
                            ->required()
                            ->afterStateUpdated(function (Closure $get, Closure $set, ?string $state) {

                                $subtotal = (int) $get('quantity') * (int) $state;
                                $set('subtotal', $subtotal);
                            })
                            ->numeric(),

                        Forms\Components\TextInput::make('subtotal')
                            ->visible(fn (Closure $get) => $get('sku'))
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->visible(fn ($state) => $state)
                            ->disabled()


                    ]),



                Forms\Components\Grid::make('total')
                    ->columns(5)
                    ->schema([
                        Forms\Components\TextInput::make('courier_charge')
                            ->required()
                            ->label('Tentative courier charge')
                            ->disabled()
                            ->helperText(function (Closure $get, Closure $set, ?Model $record, $state) {

                                $charge = Order::courierCharge($get('items'));

                                $set('courier_charge', $charge);
                                $set('packaging_charge', Order::packgingCost());

                                return 'It would be adjusted according to exact rate of courier service';
                            }),

                        Forms\Components\TextInput::make('packaging_charge')
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('total_payable')
                            ->required()
                            ->disabled()
                            ->helperText(function (Closure $get, Closure $set, ?Model $record, $state) {

                                $total_payable = Order::totalPayable($get('items'));

                                $set('total_payable', $total_payable);

                                return 'wholesale price + courier + packaging cost';
                            }),
                        Forms\Components\TextInput::make('total_saleable')
                            ->required()
                            ->disabled()
                            ->helperText(function (Closure $get, Closure $set, ?Model $record, $state) {

                                $subtotals = Order::totalSubtotals($get('items'));
                                $set('total_saleable', $subtotals);

                                //profit
                                $profit = $subtotals - $get('total_payable');
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
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(
                        fn (string $search) => Customer::query()
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%")
                            ->pluck('name', 'id')
                    )
                    ->relationship('customer', 'name')
                    ->createOptionForm([
                        Forms\Components\Grid::make('customer')
                            ->columns(2)
                            ->schema([

                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('mobile')
                                    ->label('Mobile')
                                    ->type('number')
                                    ->rules('numeric|digits_between:11,11')
                                    ->placeholder('01xxxxxxxxx')
                                    ->unique()
                                    ->required(),
                                Forms\Components\Textarea::make('address')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->email(),

                            ])
                    ]),


                Forms\Components\TextInput::make('cod')
                    ->default(1)
                    ->label('COD (total saleable + courier)')
                    ->reactive()
                    ->afterStateUpdated(function (Closure $set, $state) {
                        $set('cod_update', 2);
                    })
                    ->hint(function (Closure $get, $state, Closure $set, string $context) {


                        //cod
                        if ($get('cod_update') == 1) {

                            $subtotals = Order::totalSubtotals($get('items'));
                            $courierCharge = Order::courierCharge($get('items'));
                            $cod = $subtotals + $courierCharge;
                            $set('cod', $cod);
                        }
                        $balance = auth()->user()->balanceInt;
                        $cod = (int) $get('cod');
                        $amount = ($balance + $cod) - Order::totalPayable($get('items'));
                        logger($amount);
                        if ($amount < 0) {
                            $set('error', 1);
                            return 'Please recharge your wallet with TK = ' . abs($amount);
                        } else {
                            $set('error', 0);
                            return '';
                        };
                    })
                    ->hintColor('danger'),

                Forms\Components\Textarea::make('note_for_wholesaler')
                    ->label('Message for manufacturer if any'),
                Forms\Components\Textarea::make('note_for_courier')
                    ->label('Message for Courier if any'),

            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
                Tables\Columns\TextColumn::make('items_sum_quantity')
                    ->label('Product counts')
                    ->sum('items', 'quantity'),
                Tables\Columns\TextColumn::make('total_payable'),
                Tables\Columns\TextColumn::make('total_saleable'),
                Tables\Columns\TextColumn::make('profit'),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer Name'),
                Tables\Columns\TextColumn::make('customer.mobile')->label('Mobile'),
                Tables\Columns\TextColumn::make('customer.address')->label('Address'),
                Tables\Columns\TextColumn::make('note')
                    ->wrap(),
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
                Tables\Actions\EditAction::make()
                    ->visible(fn (?Model $record) => $record?->status?->value == OrderStatus::WaitingForWholesalerApproval->value),
                Tables\Actions\ViewAction::make()
                    ->visible(fn (?Model $record) => $record?->status?->value != OrderStatus::WaitingForWholesalerApproval->value)
                    ->mutateRecordDataUsing(function (Model $record, array $data) {

                        $data['list'] = explode('-', $data['tracking_no'])[0];

                        $items = Order::with('items')->find($data['id'])
                            ->items
                            ->map(function ($item) {

                                return [
                                    'product' => $item->sku->product->id,
                                    'sku' => $item->sku_id,
                                    'quantity' => $item->quantity,
                                    'reseller_price' => $item->reseller_price,
                                    'subtotal' => $item->total_saleable,
                                    'status' => $item->status
                                ];
                            })->toArray();

                        $data['items'] = $items;

                        return $data;
                    }),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
