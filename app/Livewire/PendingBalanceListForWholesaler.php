<?php

namespace App\Livewire;

use App\Enum\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
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

class PendingBalanceListForWholesaler extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(

                OrderItem::query()
                    ->selectRaw('order_id,sum(wholesaler_price * quantity) as amount')
                    ->where('status', OrderItemStatus::DeliveredToHub->value)
                    ->whereHas('order', function ($query) {
                        return $query->pending();
                    })
                    ->latest()
                    ->whereBelongsTo($user, 'wholesaler')
                    ->groupBy('order_id')

            )
            //->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('order.id')
                    ->weight(50)
                    ->label('Order#'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('BDT')
                    ->summarize(
                        Sum::make()
                            ->money('BDT')
                            ->label('Total')
                    ),
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return $record->order_id;
    }

    public function render()
    {
        return view('livewire.pending-balance-list');
    }
}
