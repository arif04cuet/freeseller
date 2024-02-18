<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
use Bavix\Wallet\Models\Transaction;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class DeliveryLog extends BaseWidget
{

    protected static ?int $sort = 11;
    protected int | string | array $columnSpan = 2;
    protected static bool $isLazy = false;


    public static function canView(): bool
    {
        return false;
        return auth()->user()->isSuperAdmin() || auth()->user()->isHubManager();
    }

    public function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->query(
                Order::query()
                    ->with('deliveredBy')
                    ->select([
                        'id',
                        'cod',
                        'collected_cod',
                        'delivered_at',
                        'status',
                        'delivered_by'
                    ])
                    ->whereNotNull('delivered_at')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('deliveredBy.name')
                    ->label('Added By'),
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->label('Order#'),
                Tables\Columns\TextColumn::make('cod'),
                Tables\Columns\TextColumn::make('collected_cod')->label('C.Cod'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('delivered_at')->sortable(),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('delivered_by')
                    ->options(User::query()->hubUsers()->pluck('name', 'id'))
            ]);
    }
}
