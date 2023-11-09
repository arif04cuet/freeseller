<?php

namespace App\Filament\Widgets;

use App\Enum\OrderStatus;
use App\Models\Order;
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
        /** @var App\Models\User $user */
        $user = auth()->user();
        return  $user->isSuperAdmin() || $user->isHubManager();
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
                Order::query()->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date(),
                Tables\Columns\TextColumn::make('id')
                    ->label('')
                    ->summarize(Count::make()),
                Tables\Columns\TextColumn::make('delivered')
                    ->label('')
                    ->summarize(
                        Count::make()
                            ->label('Delivered')
                            ->query(fn (Builder $query) => $query->where('status', OrderStatus::Delivered->value)),
                    ),
                Tables\Columns\TextColumn::make('return')
                    ->label('')
                    ->summarize(
                        Count::make()
                            ->label('Partial')
                            ->query(fn (Builder $query) => $query->where('status', OrderStatus::Partial_Delivered->value)),
                    ),
                Tables\Columns\TextColumn::make('cancelled')
                    ->label('')
                    ->summarize(
                        Count::make()
                            ->label('Cancelled')
                            ->query(fn (Builder $query) => $query->where('status', OrderStatus::Cancelled->value)),
                    ),


            ]);
    }
}
