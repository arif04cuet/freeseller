<?php

namespace App\Filament\Widgets;

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
                    ->select([
                        'wholesaler_id',
                        DB::raw("count(DISTINCT(order_id)) as order_count"),
                        DB::raw("sum(quantity) as item_count")
                    ])
                    ->whereDate('created_at', Carbon::today())
                    ->groupBy('wholesaler_id')
            )
            ->columns([
                Tables\Columns\TextColumn::make('wholesaler.name')
                    ->html()
                    ->formatStateUsing(
                        function (Model $record) {

                            $wholesaler = $record->wholesaler;

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
