<?php

namespace App\Filament\Widgets;

use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Bavix\Wallet\Models\Transaction;
use Closure;
use Filament\Tables;
use Filament\Forms;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ActiveWholesalerOrders extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Active Orders';

    public static function canView(): bool
    {
        return auth()->user()->isWholesaler();
    }


    protected function getTableQuery(): Builder
    {
        return Order::query()
            ->with('items')
            ->withSum([
                'items' => function (Builder $q) {
                    return $q->whereBelongsTo(auth()->user(), 'wholesaler');
                }
            ], 'quantity')
            ->withSum([
                'items' => function (Builder $q) {
                    return $q->whereBelongsTo(auth()->user(), 'wholesaler');
                }
            ], 'wholesaler_price')
            ->whereHas('items', function ($q) {
                return $q->whereBelongsTo(auth()->user(), 'wholesaler');
            })
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')->label('Order#'),
            Tables\Columns\TextColumn::make('status')
                ->badge(),
            Tables\Columns\TextColumn::make('items_sum_quantity')
                ->label('Products'),

            Tables\Columns\TextColumn::make('items_sum_wholesaler_price')
                ->label('Amount'),

        ];
    }
}
