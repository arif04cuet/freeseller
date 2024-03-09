<?php

namespace App\Filament\Widgets;

use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Bavix\Wallet\Models\Transaction;
use Carbon\Carbon;
use DB;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TodaysWholesalers extends BaseWidget
{

    protected static ?int $sort = 11;
    protected int | string | array $columnSpan = 2;
    protected static bool $isLazy = false;

    protected static ?string $heading = 'Collection Pending';

    public function getTableRecordKey(Model $record): string
    {
        return $record->wholesaler_id;
    }

    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin() || auth()->user()->isHubManager();
    }

    public function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->query(
                OrderItem::query()
                    ->with('wholesaler.business')
                    ->whereHas(
                        'order',
                        fn ($q) => $q->whereIn('status', [
                            OrderStatus::WaitingForWholesalerApproval->value,
                            OrderStatus::WaitingForHubCollection->value
                        ])->doesntHave('collections')
                    )
                    ->select([
                        'wholesaler_id',
                        DB::raw("count(DISTINCT(order_id)) as order_count"),
                        DB::raw("sum(quantity) as item_count")
                    ])
                    //->whereDate('created_at', Carbon::today())
                    ->groupBy('wholesaler_id')
            )
            ->columns([
                Tables\Columns\TextColumn::make('wholesaler')
                    ->html()
                    ->formatStateUsing(
                        function ($state) {

                            $wholesaler = $state;

                            return '<a href="tel:' . $wholesaler->mobile . '">' .
                                $wholesaler->business->name . '<br/>' .
                                $wholesaler->id_number .
                                '</a>';
                        }
                    ),

                Tables\Columns\TextColumn::make('order_count'),
                Tables\Columns\TextColumn::make('item_count'),

            ]);
    }
}
