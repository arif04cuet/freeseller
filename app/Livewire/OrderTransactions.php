<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use Bavix\Wallet\Models\Transaction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderTransactions extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Order $order;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->select(
                        '*',
                        'meta->order AS order_no'
                    )
                    ->where('meta->order', $this->order->id)
                    ->whereMorphedTo('payable', auth()->user())
            )
            ->defaultGroup(
                Tables\Grouping\Group::make('order_no')
                    ->collapsible(),
            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('order_no'),
                Tables\Columns\TextColumn::make('amount')
                    ->summarize(
                        Sum::make()
                            ->label('Profit')
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
            ]);
    }

    public function render(): View
    {
        return view('livewire.order-transactions');
    }
}
