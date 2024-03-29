<?php

namespace App\Filament\Widgets;

use App\Enum\OrderStatus;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class ActiveOrdersForReseller extends BaseWidget
{
    protected static bool $isLazy = false;
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 2;
    protected static ?string $heading = 'Active Orders';

    public static function canView(): bool
    {
        return auth()->user()->isReseller();
    }


    public function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->query(
                Order::query()
                    ->whereBelongsTo(auth()->user(), 'reseller')
                    ->whereNotIn('status', [
                        OrderStatus::Delivered->value,
                        OrderStatus::Partial_Delivered->value,
                        OrderStatus::Cancelled->value,
                    ])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
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
                Tables\Columns\TextColumn::make('customer.mobile')
                    ->searchable()
                    ->formatStateUsing(fn (Order $record, $state) => '<a href="tel:' . $state . '"><u>' . $record->customer->name . '<br/>' . $state . '</u></a>')
                    ->html()
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('cod')
                    ->label('COD'),
                Tables\Columns\TextColumn::make('consignment_id')
                    ->label('CN'),
                Tables\Columns\TextColumn::make('tracking_code')
                    ->label('Track')
                    ->formatStateUsing(fn () => 'Track Order')
                    ->url(fn (Order $record) => $record->tracking_url)
                    ->openUrlInNewTab(),
                // Tables\Columns\TextColumn::make('items_sum_quantity')
                //     ->label('Products')
                //     ->sum('items', 'quantity'),
                // Tables\Columns\TextColumn::make('total_payable')
                //     ->label('Payable'),
                // Tables\Columns\TextColumn::make('total_saleable')
                //     ->label('Sallable'),

                //Tables\Columns\TextColumn::make('profit'),
            ]);
    }
}
