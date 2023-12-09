<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Bavix\Wallet\Models\Transaction;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class Transactions extends BaseWidget
{
    use CanPoll;

    protected static ?int $sort = 11;
    protected int | string | array $columnSpan = 2;

    public Order $order;

    public static function canView(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->defaultGroup(
                Tables\Grouping\Group::make('order_no')
                    ->collapsible(),
            )

            ->query(
                Transaction::query()
                    ->select(
                        '*',
                        'meta->order AS order_no'
                    )
                    ->whereMorphedTo('payable', auth()->user())
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_no')
                    ->label('Order#')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('meta->order', $search);
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->summarize(
                        Sum::make()
                            ->label(fn () => auth()->user()->isReseller() ? 'Profit' : 'Sum')
                            ->formatStateUsing(fn ($state) => (float) ($state / 100))
                    )
                    ->formatStateUsing(fn ($state) => (float) ($state / 100))
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
