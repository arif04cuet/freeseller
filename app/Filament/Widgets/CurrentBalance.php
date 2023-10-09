<?php

namespace App\Filament\Widgets;

use App\Enum\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CurrentBalance extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        /** @var App\Models\User $user */
        $user = auth()->user();
        $platformPer = (int) config('freeseller.platform_fee');
        $codPer = (int) config('freeseller.cod_fee');

        $cards = [];

        if ($user->isReseller() || $user->isWholesaler() || $user->isSuperAdmin()) {

            $percentageFn = fn ($amount, $percentage): float => (float) (($percentage / 100) * $amount);

            $balance = $user->balanceFloat;

            $pendingBalance = 0;

            if ($user->isReseller()) {

                //minus lock ammount from balance
                $lockAmount = (int) auth()->user()->lockAmount->sum('amount');
                $balance = $balance - $lockAmount;

                $pendingSum = $this->resellerPendingSum($user);
                $pendingBalance = $pendingSum - $percentageFn($pendingSum, $platformPer + $codPer);
            }

            if ($user->isWholesaler()) {
                $pendingSum = $this->wholesalerPendingSum($user);
                $pendingBalance = $pendingSum - $percentageFn($pendingSum, $platformPer + $codPer);
            }

            if ($user->isSuperAdmin()) {
                $pendingSum = $this->platforrmPendingSum($user);
                $pendingBalance = $percentageFn($pendingSum, $platformPer + $codPer);
            }

            //
            $msg = 'Balance you can windraw';
            $color = 'success';
            if ($user->isReseller() && $balance < config('freeseller.minimum_acount_balance')) {
                $msg = new HtmlString(
                    'Your current balance is less than ' . config('freeseller.minimum_acount_balance') .
                        ' <a class="bg-red-500 font-bold rounded" href="' . route('filament.app.resources.wallet-recharge-requests.index') . '">Recharge</a>'
                );
                $color = 'danger';
            }

            $cards[] = Stat::make('Available Balance (TK)', $balance)
                ->description($msg)
                ->color($color);

            $cards[] = Stat::make('Pending Balance (TK)', $pendingBalance)
                ->description('Balance for your in-progress orders')
                ->color('warning');
        }
        if ($user->isReseller()) {
            $lockAmount = $user->lockAmount->sum('amount');
            $cards[] = Stat::make('Lock Amount (TK)', $lockAmount)
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

    public function resellerPendingSum(User $user): int
    {

        return Order::query()
            ->pending()
            ->whereBelongsTo($user, 'reseller')
            ->whereColumn('cod', '>', 'total_payable')
            ->sum(DB::raw('cod - total_payable'));
    }

    public function wholesalerPendingSum(User $user): int
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

        $wholesalersSum = OrderItem::query()
            ->where('status', OrderItemStatus::Approved->value)
            ->whereHas('order', function ($query) {
                return $query->pending();
            })
            ->sum('wholesaler_price');

        return $resellersSum + $wholesalersSum;
    }
}
