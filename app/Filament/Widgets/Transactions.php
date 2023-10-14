<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Bavix\Wallet\Models\Transaction;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class Transactions extends BaseWidget
{
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 6;

    public Order $order;

    public static function canView(): bool
    {
        return !auth()->user()->isHubManager();
    }

    public function table(Table $table): Table
    {
        return $table

            ->query(
                Transaction::query()
                    ->whereMorphedTo('payable', auth()->user())
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('meta.order')
                    ->label('Order#')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('meta->order', $search);
                    }),
                Tables\Columns\TextColumn::make('amount_float')
                    ->label('Amount'),
                Tables\Columns\TextColumn::make('type')
                    ->colors([
                        'success' => 'deposit',
                        'danger' => 'withdraw',
                    ]),

                Tables\Columns\TextColumn::make('meta.description'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ]);
    }
}
