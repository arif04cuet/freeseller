<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Bavix\Wallet\Models\Transaction;
use Closure;
use Filament\Tables;
use Filament\Forms;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class Transactions extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected function getTableQuery(): Builder
    {
        return Transaction::query()
            ->whereMorphedTo('payable', auth()->user())
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [

            Tables\Columns\TextColumn::make('amount')
                ->searchable(),
            Tables\Columns\BadgeColumn::make('type')
                ->colors([
                    'success' => 'deposit',
                    'danger' => 'withdraw',
                ]),
            Tables\Columns\TextColumn::make('meta.description'),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
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
                })
        ];
    }
}
