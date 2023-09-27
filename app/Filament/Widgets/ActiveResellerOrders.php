<?php

namespace App\Filament\Widgets;

use App\Enum\OrderStatus;
use App\Models\Order;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
            ->whereNotIn('status', [
                OrderStatus::Delivered->value,
            ])
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label('Order#')
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
            Tables\Columns\TextColumn::make('status')
                ->badge(),
            Tables\Columns\TextColumn::make('consignment_id')
                ->label('CN'),
            Tables\Columns\TextColumn::make('track')
                ->label('Track')
                ->url(fn (Order $record) => 'https://steadfast.com.bd/t/' . $record->tracking_code)
                ->openUrlInNewTab(),
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
