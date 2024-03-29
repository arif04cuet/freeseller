<?php

namespace App\Filament\Widgets;

use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use DB;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Query\Builder;

use function Filament\Support\format_number;

class DailyOrder extends BaseWidget
{
    protected static ?int $sort = 8;
    protected int | string | array $columnSpan = 2;
    protected static bool $isLazy = false;

    public bool $dataLoaded = false;

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin() || auth()->user()->isHubManager();
    }

    public function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->query(
                Order::query()
                    ->select([
                        DB::raw('Max(id) as id'),
                        DB::raw('DATE(created_at) as created_at'),
                        DB::raw('count(id) as order_count'),
                        DB::raw("
                            CASE
                                WHEN (status != 'cacelled' and collected_cod is null) THEN SUM(cod)
                                WHEN (status != 'cacelled' and collected_cod is not null) THEN SUM(collected_cod)
                                ELSE 0
                            END as cod
                    ")
                    ])
                    ->groupBy(DB::raw("DATE(created_at)"))
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date(),
                Tables\Columns\TextColumn::make('order_count')
                    ->label('Orders'),
                Tables\Columns\TextColumn::make('cod'),
            ]);
    }

    public function table_old(Table $table): Table
    {

        $platformFree = config('freeseller.platform_fee');
        $courierCharge = config('freeseller.delivery_charge');
        $packagingFree = config('freeseller.packaging_fee');

        $incomeFromCourier = 10; // we get 120 taka from reseller and we give 110 taka to steadfast. so tentative 10 taka income here.
        $othersPlatformIncome = $packagingFree + $incomeFromCourier;

        return $table
            ->deferLoading()
            ->query(
                Order::query()
                    ->select(
                        [
                            'orders.*',
                            DB::raw("
                                CASE
                                    WHEN (status != 'cacelled' and profit > 1) THEN (round((((total_payable + profit - (courier_charge + {$packagingFree}))/100)* {$platformFree}),2)+{$othersPlatformIncome})
                                    WHEN (status != 'cacelled' and profit < 1) THEN (round((((total_payable- (courier_charge + {$packagingFree}))/100)* {$platformFree}),2)+{$othersPlatformIncome})
                                    WHEN (status = 'cacelled' and delivered_at is not null) THEN $othersPlatformIncome
                                    ELSE 0
                                END as platform_profit
                            "),
                            DB::raw("CASE WHEN (status = 'cacelled' and delivered_at is null) THEN 0 ELSE profit END as reseller_profit")
                        ]
                    )
                    ->withSum([
                        'items' => function ($q) {
                            return $q->when(
                                auth()->user()->isWholesaler(),
                                fn ($q) => $q->whereBelongsTo(auth()->user(), 'wholesaler')
                            );
                        },
                    ], 'quantity')
                    ->addSelect([
                        'wholesaler_total' => OrderItem::query()
                            ->whereColumn('order_id', 'orders.id')
                            ->whereBelongsTo(auth()->user(), 'wholesaler')
                            ->selectRaw('sum(CASE WHEN status in ("returned","cancelled") THEN 0 ELSE (wholesaler_price * quantity) END) as total'),


                    ])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date(),
                Tables\Columns\TextColumn::make('id')
                    ->label('id')
                    ->summarize(Count::make()->label('Orders')),

                Tables\Columns\TextColumn::make('items_sum_quantity')
                    ->label('Products')
                    ->summarize(
                        Sum::make()
                            ->label('Products')
                    ),

                Tables\Columns\TextColumn::make('cod')
                    ->label('Cod')
                    ->visible(fn () => !auth()->user()->isWholesaler())
                    ->summarize(
                        Sum::make()
                            ->label('Cod')
                    ),

                Tables\Columns\TextColumn::make('reseller_profit')
                    ->label('Profit')
                    ->visible(fn () => auth()->user()->isReseller())
                    ->summarize(
                        Sum::make()
                            ->label('Profit')
                    ),

                Tables\Columns\TextColumn::make('platform_profit')
                    ->label('P. Profit')
                    ->visible(fn () => auth()->user()->isSuperAdmin())
                    ->summarize(
                        Sum::make()
                            ->label('P.Profit')
                    ),
                Tables\Columns\TextColumn::make('wholesaler_total')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => (int)$state)
                    ->visible(fn () => auth()->user()->isWholesaler())
                    ->summarize(
                        Sum::make()
                            ->label('Amount')
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

            ]);
    }
}
