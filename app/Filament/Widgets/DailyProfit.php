<?php

namespace App\Filament\Widgets;

use App\Enum\OrderStatus;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DailyProfit extends BaseWidget
{
    protected static ?int $sort = 10;
    protected int | string | array $columnSpan = 2;

    public static function canView(): bool
    {
        /** @var App\Models\User $user */
        $user = auth()->user();
        return  $user->isReseller();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultGroup(
                Tables\Grouping\Group::make('created_at')
                    ->date()
                    ->collapsible()
            )
            ->groupsOnly()
            ->query(
                Order::query()
                    ->with('items')
                    ->whereIn('status', [
                        OrderStatus::Delivered->value,
                        OrderStatus::Partial_Delivered->value,
                    ])
                    ->mine()
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date(),
                Tables\Columns\TextColumn::make('profit')
                    ->label('')
                    ->summarize(Sum::make()->label('Total')),
            ]);
    }
}
