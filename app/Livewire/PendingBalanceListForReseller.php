<?php

namespace App\Livewire;

use App\Models\Order;
use Bavix\Wallet\Models\Transaction;
use DB;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\Component;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;

class PendingBalanceListForReseller extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                Order::query()
                    ->select([
                        '*',
                        DB::raw('cod - total_payable AS amount')
                    ])
                    ->pending()
                    ->whereBelongsTo($user, 'reseller')
                    ->whereColumn('cod', '>', 'total_payable')
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->weight(50)
                    ->label('Order#'),
                Tables\Columns\TextColumn::make('cod'),
                Tables\Columns\TextColumn::make('total_payable'),
                Tables\Columns\TextColumn::make('amount')
                    ->summarize(
                        Sum::make()
                            ->label('Total')
                    ),
            ]);
    }

    public function render()
    {
        return view('livewire.pending-balance-list');
    }
}