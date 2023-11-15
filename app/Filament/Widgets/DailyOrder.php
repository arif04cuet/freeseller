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

class DailyOrder extends BaseWidget
{
    protected static ?int $sort = 11;
    protected int | string | array $columnSpan = 2;

    public static function canView(): bool
    {
        return true;
        // /** @var App\Models\User $user */
        // $user = auth()->user();
        // return  $user->isSuperAdmin() || $user->isHubManager();
    }

    public function table(Table $table): Table
    {
        $platformFree = config('freeseller.platform_fee');
        $courierCharge = config('freeseller.delivery_charge');
        $packagingFree = config('freeseller.delivery_charge');

        return $table
            ->defaultGroup(
                Tables\Grouping\Group::make('created_at')
                    ->label('')
                    ->date()
                    ->collapsible()
            )
            ->query(
                Order::query()
                    ->when(auth()->user()->isWholesaler(), function ($q) {
                        return $q->whereHas('items', function ($q) {
                            return $q->whereBelongsTo(auth()->user(), 'wholesaler');
                        });
                    })
                    ->when(auth()->user()->isReseller(), function ($q) {
                        return $q->whereBelongsTo(auth()->user(), 'reseller');
                    })
                    ->select(
                        [
                            'orders.*',
                            DB::raw("
                                CASE
                                    WHEN (status != 'cacelled' and profit > 1) THEN (round((((total_payable + profit - ({$courierCharge} + {$packagingFree}))/100)* {$platformFree}),2)+20)
                                    WHEN (status != 'cacelled' and profit < 1) THEN (round((((total_payable-{$courierCharge})/100)* {$platformFree}),2)+20)
                                    ELSE 0
                                END as platform_profit
                            "),
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
                    // ->addSelect([
                    //     'wholesaler_total' => OrderItem::query()
                    //         ->whereColumn('order_id', 'orders.id')
                    //         ->whereBelongsTo(auth()->user(), 'wholesaler')
                    //         ->selectRaw('sum(CASE WHEN status in ("returned","cancelled") THEN 0 ELSE (wholesaler_price * quantity) END) as total'),


                    // ])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date(),
                Tables\Columns\TextColumn::make('id')
                    ->label('id')
                    ->summarize(Count::make()->label('Orders')),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('items_sum_quantity')
                    ->label('Products')
                    ->summarize(
                        Sum::make()
                            ->label('Products')
                    ),

                Tables\Columns\TextColumn::make('cod')
                    ->label('Cod')
                    ->visible(fn () => auth()->user()->isReseller() || auth()->user()->isSuperAdmin())
                    ->summarize(
                        Sum::make()
                            ->label('Cod')
                    ),
                Tables\Columns\TextColumn::make('platform_profit')
                    ->label('P. Profit')
                    ->visible(fn () => auth()->user()->isSuperAdmin())
                    ->summarize(
                        Sum::make()
                            ->label('P.Profit')
                    ),
                // Tables\Columns\TextColumn::make('wholesaler_total')
                //     ->label('Amount')
                //     ->visible(fn () => auth()->user()->isWholesaler())
                //     ->summarize(
                //         Sum::make()
                //             ->label('Amount')
                //     ),

            ]);
    }
}
