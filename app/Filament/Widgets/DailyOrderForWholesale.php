<?php

namespace App\Filament\Widgets;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\OrderCollection;
use App\Models\OrderItem;
use DB;
use Filament\Actions\StaticAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Notification;

class DailyOrderForWholesale extends BaseWidget
{
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 2;
    protected static bool $isLazy = false;

    protected static ?string $heading = 'Daily Parcels delivered to Hub';

    public static function canView(): bool
    {
        return auth()->user()->isWholesaler();
    }

    protected function resolveTableRecord(?string $key): ?Model
    {
        return OrderCollection::query()
            ->select('collected_at')
            ->whereDate('collected_at', $key)
            ->first();
    }

    public function getTableRecordKey(Model $record): string
    {
        return $record->collected_at;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderCollection::query()
                    ->where('wholesaler_id', auth()->user()->id)
                    ->select([
                        DB::raw('DATE(collected_at) as collected_at'),
                        DB::raw('count(order_id) as order_count')
                    ])
                    ->groupBy(DB::raw("DATE(collected_at)"))
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('collected_at')
                    ->label('Date')
                    ->date(),
                Tables\Columns\TextColumn::make('order_count')
                    ->label('Parcels')
            ])
            ->actions([
                Tables\Actions\Action::make('view_order')
                    ->label('View')
                    ->button()
                    ->modalCancelAction(false)
                    ->modalSubmitAction(false)
                    ->modalContent(fn (Model $record) => view('orders.collected-by-date', compact('record')))
            ]);
    }
}
