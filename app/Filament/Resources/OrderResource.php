<?php

namespace App\Filament\Resources;

use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
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
        return auth()->user()->hasAnyRole([SystemRole::Reseller->value]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->mine();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('list')
                    ->options(auth()->user()->lists->pluck('name', 'id'))
                    ->reactive(),



                Repeater::make('items')
                    ->columnSpanFull()
                    ->columns(6)
                    ->cloneable()
                    ->reactive()
                    ->disableItemCreation(fn (?Model $record) => $record?->status?->value != OrderStatus::WaitingForWholesalerApproval->value)
                    ->schema([
                        Forms\Components\Select::make('product')
                            ->options(
                                fn (Closure $get) => !$get('../../list') ? [] : auth()->user()
                                    ->lists()
                                    ->where('id', $get('../../list'))
                                    ->first()
                                    ->products
                                    ->pluck('name', 'id')
                            )
                            ->reactive()
                            ->required(),
                        Forms\Components\Select::make('sku')
                            ->label('Item')
                            ->reactive()
                            ->required()
                            ->visible(fn (Closure $get) => $get('product'))
                            ->options(fn (Closure $get) => Sku::query()
                                ->where('product_id', $get('product'))
                                ->where('quantity', '>', 0)
                                ->pluck('name', 'id')),

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


                Forms\Components\TextInput::make('total_amount')
                    ->label('COD')
                    ->placeholder(function (Closure $get, Closure $set, ?Model $record) {

                        $cod = collect($get('items'))->sum('subtotal');
                        $set('total_amount', $cod);
                        return $cod;
                    }),

                Forms\Components\Textarea::make('note')
                    ->label('Message for manufacturer if any'),

            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('total_amount'),
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
                                    'subtotal' => $item->total_amount,
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
