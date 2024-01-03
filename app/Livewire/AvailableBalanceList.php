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
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;

use function Filament\Support\format_money;

class AvailableBalanceList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $query->simplePaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->heading(
                fn () => '' . format_money(auth()->user()->active_balance, 'BDT')
            )
            ->query(
                Transaction::query()
                    ->select(
                        'type',
                        DB::raw("max(DATE(created_at)) as date"),
                        DB::raw("CONCAT('BDT ',round(sum(amount/100),2)) as amount"),
                        DB::raw("
                                CASE
                                    WHEN JSON_VALUE(meta,'$.order') THEN CONCAT('order','#',JSON_VALUE(meta,'$.order'))
                                    WHEN JSON_VALUE(meta,'$.wallet_recharge') THEN CONCAT('recharge','#',JSON_VALUE(meta,'$.wallet_recharge'))
                                    WHEN JSON_VALUE(meta,'$.claim') THEN CONCAT('claim','#',JSON_VALUE(meta,'$.claim'))
                                    ELSE CONCAT('fund','#',id)
                                END as item_type
                            ")
                    )
                    ->whereMorphedTo('payable', auth()->user())
                    ->groupBy('item_type', 'type')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('item_type')
                    ->label('Item')
                    ->grow(false)
                    ->getStateUsing(fn (Model $record) => view('transaction.available-item-name', ['record' => $record])->render())
                    ->html(),
                Tables\Columns\TextColumn::make('amount')
                    ->grow(false)
                    ->color(fn (Model $record): string => match ($record->type) {
                        'deposit' => 'success',
                        'withdraw' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('transaction')
                    ->label('Tnx Type')
                    ->form([
                        Forms\Components\Radio::make('type')
                            ->inline()
                            ->options([
                                'all' => 'All',
                                'deposit' => 'Deposit',
                                'withdraw' => 'Withdraw',
                            ])
                    ])
                    ->query(
                        fn (Builder $query, array $data): Builder => $query
                            ->when($data['type'] == 'deposit', fn ($q) => $q->where('type', 'deposit'))
                            ->when($data['type'] == 'withdraw', fn ($q) => $q->where('type', 'withdraw'))
                    )
            ], layout: FiltersLayout::AboveContentCollapsible);
    }

    public function getTableRecordKey(Model $record): string
    {
        return $record->item_type;
    }


    public function render()
    {
        return view('livewire.pending-balance-list');
    }
}
