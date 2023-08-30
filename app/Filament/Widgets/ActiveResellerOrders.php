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

class ActiveResellerOrders extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Active Orders';

    public static function canView(): bool
    {
        return auth()->user()->isReseller();
    }


    protected function getTableQuery(): Builder
    {
        return Order::query()
            ->whereBelongsTo(auth()->user(), 'reseller')
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')->label('Order#'),
            Tables\Columns\BadgeColumn::make('status')
                ->enum(OrderStatus::array())
                ->colors([
                    'secondary' =>  OrderStatus::WaitingForWholesalerApproval->value,
                    'warning' =>  OrderStatus::Processing->value,
                    'success' => OrderStatus::Delivered->value,
                    'danger' => OrderStatus::Cancelled->value,
                ]),
            Tables\Columns\TextColumn::make('items_sum_quantity')
                ->label('Products')
                ->sum('items', 'quantity'),
            Tables\Columns\TextColumn::make('total_payable')
                ->label('Payable'),
            Tables\Columns\TextColumn::make('total_saleable')
                ->label('Sallable'),
            Tables\Columns\TextColumn::make('cod')
                ->label('COD'),
            Tables\Columns\TextColumn::make('profit'),

        ];
    }
}
