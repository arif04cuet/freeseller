<?php

namespace App\Livewire;

use App\Enum\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderCollection;
use App\Models\OrderItem;
use App\Models\UserLockAmount;
use Bavix\Wallet\Models\Transaction;
use Carbon\Carbon;
use DB;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\Component;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use function Filament\Support\format_money;

class CollectedParcelListByDate extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Carbon $date;

    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $query->simplePaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    }

    public function table(Table $table): Table
    {
        $collectedAt = $this->date;

        return $table
            ->query(
                Order::query()
                    ->with(['items', 'collections'])
                    ->addSelect([
                        'total' => OrderItem::query()
                            ->whereColumn('order_id', 'orders.id')
                            ->whereBelongsTo(auth()->user(), 'wholesaler')
                            ->selectRaw('sum(wholesaler_price * quantity) as total')
                            ->whereIn('status', [
                                OrderItemStatus::DeliveredToHub->value,
                                OrderItemStatus::Delivered->value,
                            ])
                    ])
                    ->whereHas('collections', function ($q) use ($collectedAt) {
                        return $q->whereBelongsTo(auth()->user(), 'wholesaler')
                            ->whereDate('collected_at', $collectedAt->format('Y-m-d'));
                    })
                    ->whereHas('items', function ($q) {
                        return $q->whereBelongsTo(auth()->user(), 'wholesaler');
                    })
                    ->withSum([
                        'items' => function (Builder $q) {
                            return $q->whereBelongsTo(auth()->user(), 'wholesaler');
                        },
                    ], 'quantity')
                    ->latest()

            )
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('status')
                    ->wrap()
                    ->badge(),
                Tables\Columns\TextColumn::make('items_sum_quantity')
                    ->label('Products'),

                Tables\Columns\TextColumn::make('total')
                    ->summarize(Sum::make())
                    ->label('Amount')
            ]);
    }


    public function render()
    {
        return view('livewire.lock-amount-list');
    }
}
