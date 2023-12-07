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

class PendingBalancePlatform extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 1;

    protected function getColumns(): int
    {
        return 1;
    }
    protected function getStats(): array
    {
        /** @var App\Models\User $user */
        $user = auth()->user();
        $platformPer = (int) config('freeseller.platform_fee');
        $codPer = (int) config('freeseller.cod_fee');

        $percentageFn = fn ($amount, $percentage): float => (float) (($percentage / 100) * $amount);

        $pendingSum = $this->platforrmPendingSum($user);
        $pendingBalance = $percentageFn($pendingSum, $platformPer + $codPer);

        return [
            Stat::make('Pending Balance (TK)', $pendingBalance)
                //->description('Balance for your in-progress orders')
                ->color('warning')
        ];
    }

    public static function canView(): bool
    {
        /** @var App\Models\User $user */
        $user = auth()->user();

        return $user->isSuperAdmin();
    }



    public function platforrmPendingSum()
    {
        $resellersSum = Order::query()
            ->pending()
            ->whereColumn('cod', '>', 'total_payable')
            ->sum(DB::raw('cod - total_payable'));

        $wholesalersSum = OrderItem::query()
            ->where('status', OrderItemStatus::DeliveredToHub->value)
            ->whereHas('order', function ($query) {
                return $query->pending();
            })
            ->sum('wholesaler_price');

        return $resellersSum + $wholesalersSum;
    }
}
