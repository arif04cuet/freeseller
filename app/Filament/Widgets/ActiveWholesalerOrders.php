<?php

namespace App\Filament\Widgets;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Models\Order;
use Filament\Actions\StaticAction;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Notification;

class ActiveWholesalerOrders extends BaseWidget
{
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 2;

    protected static ?string $heading = 'Active Orders';

    public static function canView(): bool
    {
        return auth()->user()->isWholesaler();
    }

    protected function getTableQuery(): Builder
    {
        return Order::query()
            ->with('items')
            ->whereHas('items', function ($q) {
                return $q->whereBelongsTo(auth()->user(), 'wholesaler')
                    ->whereNotIn('status', [
                        OrderItemStatus::Cancelled->value,
                        OrderItemStatus::Returned->value
                    ]);
            })
            ->whereNotIn('status', [
                OrderStatus::Delivered->value,
                OrderStatus::Partial_Delivered->value,
                OrderStatus::Cancelled->value,
            ])
            ->withSum([
                'items' => function (Builder $q) {
                    return $q->whereBelongsTo(auth()->user(), 'wholesaler');
                },
            ], 'quantity')
            ->withSum([
                'items' => function (Builder $q) {
                    return $q->whereBelongsTo(auth()->user(), 'wholesaler');
                },
            ], 'wholesaler_price')
            ->whereHas('items', function ($q) {
                return $q->whereBelongsTo(auth()->user(), 'wholesaler');
            })
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label('Order#')
                ->formatStateUsing(fn ($state) => '<u>' . $state . '</u>')
                ->html()
                ->action(
                    Tables\Actions\Action::make('items')
                        ->label('Products')
                        ->icon('heroicon-o-bars-4')
                        ->iconButton()
                        ->action(
                            function (Tables\Actions\Action $action, $data, Order $record) {

                                if (isset($data['collector_code'])) {

                                    $collection = $record->collections->filter(fn ($item) => $item->wholesaler_id == auth()->user()->id)->first();

                                    if (!$collection || ($collection->collector_code != $data['collector_code'])) {

                                        Notification::make()
                                            ->title('Code mismatch')
                                            ->danger()
                                            ->send();

                                        $action->halt();
                                    }

                                    $record->deliverToCollector($collection);
                                } else {
                                    $record->getItemsByWholesaler(auth()->user())->each->markAsApproved();
                                }
                            }
                        )
                        ->modalSubmitAction(
                            function (StaticAction $action, Order $record) {

                                $wholesalerPendingItems = $record
                                    ->getItemsByWholesaler(auth()->user(), OrderItemStatus::WaitingForWholesalerApproval->value)
                                    ->count();
                                return $wholesalerPendingItems ? $action->label('Approve All') : false;
                            }
                        )
                        ->modalHeading('Products details')
                        ->modalContent(fn (Model $record) => view('orders.items-status', [
                            'items' => $record->loadMissing('items.wholesaler')->getItemsByWholesaler(auth()->user()),
                        ])),
                ),
            Tables\Columns\TextColumn::make('status')
                ->wrap()
                ->badge(),
            Tables\Columns\TextColumn::make('items_sum_quantity')
                ->label('Products'),

            Tables\Columns\TextColumn::make('items_sum_wholesaler_price')
                ->label('Amount'),
            Tables\Columns\TextColumn::make('created_at')
                ->since()

        ];
    }
}
