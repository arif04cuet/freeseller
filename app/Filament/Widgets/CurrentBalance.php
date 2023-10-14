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
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 4;

    protected function getColumns(): int
    {
        return 1;
    }

    protected function getStats(): array
    {
        /** @var App\Models\User $user */
        $user = auth()->user();

        $balance = $user->balanceFloat;

        if ($user->isReseller()) {

            //minus lock amount from balance
            $lockAmount = (int) auth()->user()->lockAmount->sum('amount');
            $balance = $balance - $lockAmount;
        }


        //
        $msg = 'Balance you can windraw';
        $color = 'success';
        if ($user->isReseller() && $balance < config('freeseller.minimum_acount_balance')) {
            $msg = new HtmlString(
                'Your current balance is less than ' . config('freeseller.minimum_acount_balance') .
                    ' <a class="bg-red-500 font-bold rounded" href="' .
                    route('filament.app.resources.wallet-recharge-requests.index') . '">Recharge</a>'
            );
            $color = 'danger';
        }

        return [
            Stat::make('Available Balance (TK)', $balance)
                ->description($msg)
                ->color($color)
        ];
    }

    public static function canView(): bool
    {
        /** @var App\Models\User $user */
        $user = auth()->user();

        return $user->isSuperAdmin() || $user->isWholesaler() || $user->isReseller();
    }
}
