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
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LockAmountList extends Component implements HasForms, HasTable
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
            ->query(
                UserLockAmount::query()
                    ->whereBelongsTo($user)

            )
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('for')
                    ->getStateUsing(fn (UserLockAmount $record) => $record->details()['label']),

                Tables\Columns\TextColumn::make('entity_id')
                    ->url(fn (UserLockAmount $record) => $record->details()['url'])
                    ->openUrlInNewTab()
                    ->label('Id#'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('BDT')
                    ->summarize(Sum::make()->label('Total')->money('BDT')),
            ]);
    }

    // public function getTableRecordKey(Model $record): string
    // {
    //     return $record->order_id;
    // }


    public function render()
    {
        return view('livewire.lock-amount-list');
    }
}
