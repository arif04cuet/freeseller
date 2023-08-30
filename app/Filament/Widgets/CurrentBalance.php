<?php

namespace App\Filament\Widgets;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\DB;

class CurrentBalance extends BaseWidget
{

    protected function getCards(): array
    {
        /** @var App\Models\User $user */
        $user = auth()->user();
        $platformPer = (int)config('freeseller.platform_fee');
        $codPer = (int) config('freeseller.cod_fee');

        $cards = [];

        if ($user->isReseller() || $user->isWholesaler() || $user->isSuperAdmin()) {

            $percentageFn = fn ($amount, $percentage): float => (float) (($percentage / 100) * $amount);

            $balance = $user->balance;
            $pendingBalance = 0;

            if ($user->isReseller())
                $pendingBalance = $percentageFn($this->resellerPendingSum($user), $platformPer + $codPer);

            if ($user->isWholesaler())
                $pendingBalance = $percentageFn($this->wholesalerPendingSum($user), $platformPer + $codPer);

            if ($user->isSuperAdmin())
                $pendingBalance = $percentageFn($this->platforrmPendingSum(), $platformPer + $codPer);;


            $cards[] = Card::make('Available Balance (TK)', $balance)
                ->description('Balance you can windraw')
                ->color('success');
            $cards[] = Card::make('Pending Balance (TK)', $pendingBalance)
                ->description('Balance for your in-progress orders')
                ->color('warning');
        }
        if ($user->isReseller()) {
            $lockAmount = $user->lockAmount->sum('amount');
            $cards[] = Card::make('Lock Amount (TK)', $lockAmount)
                ->description('Balance, currently locked ')
                ->color('danger');
        }
        return $cards;
    }

    public static function canView(): bool
    {
        /** @var App\Models\User $user */
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->isWholesaler() || $user->isReseller();
    }

    public  function resellerPendingSum(User $user): int
    {

        return Order::query()
            ->pending()
            ->whereBelongsTo($user, 'reseller')
            ->whereColumn('cod', '>', 'total_payable')
            ->sum(DB::raw('cod - total_payable'));
    }

    public  function wholesalerPendingSum(User $user): int
    {

        return OrderItem::query()
            ->where('status', OrderItemStatus::Approved->value)
            ->whereHas('order', function ($query) {
                return $query->pending();
            })
            ->whereBelongsTo($user, 'wholesaler')
            ->sum('wholesaler_price');
    }
    public function platforrmPendingSum()
    {
        $resellersSum = Order::query()
            ->pending()
            ->whereColumn('cod', '>', 'total_payable')
            ->sum(DB::raw('cod - total_payable'));

        $wholesalersSum =  OrderItem::query()
            ->where('status', OrderItemStatus::Approved->value)
            ->whereHas('order', function ($query) {
                return $query->pending();
            })
            ->sum('wholesaler_price');

        return $resellersSum + $wholesalersSum;
    }
}
