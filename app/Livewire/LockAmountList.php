<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\UserLockAmount;
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
use Illuminate\Database\Eloquent\Model;

class LockAmountList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(
                UserLockAmount::query()
                    ->selectRaw('order_id,sum(amount) as amount')
                    ->whereBelongsTo($user)
                    ->groupBy('order_id')
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->weight(50)
                    ->label('Order#'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('BDT')
                    ->summarize(Sum::make()->label('Total')->money('BDT')),
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return $record->order_id;
    }


    public function render()
    {
        return view('livewire.lock-amount-list');
    }
}
