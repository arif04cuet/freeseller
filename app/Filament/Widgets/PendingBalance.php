<?php

namespace App\Filament\Widgets;

use App\Enum\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use DB;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;

class PendingBalance extends Widget implements HasForms, HasActions
{
    use CanPoll;

    use InteractsWithActions;
    use InteractsWithForms;

    protected static bool $isLazy = false;
    protected static string $view = 'filament.widgets.pending-balance';
    protected int | string | array $columnSpan = [
        'default' => 2,
        'md' => 1,
    ];
    protected static ?int $sort = 5;

    protected function getPollingInterval(): ?string
    {
        return null;
    }

    public function listAction(): Action
    {
        return Action::make('list')
            ->label('Details')
            ->icon('heroicon-o-eye')
            ->iconButton()
            ->modalHeading('Pending Balance')
            ->modalContent(view('transaction.pending-balance'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->action(fn () => dd('ok'));
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        $pendingBalance = 0;

        if ($user->isReseller())
            $pendingBalance = $this->resellerPendingSum($user);

        if ($user->isWholesaler())
            $pendingBalance = $this->wholesalerPendingSum($user);

        $balance = Stat::make(
            new HtmlString('Pending Balance (TK)'),
            $pendingBalance
        )
            ->description('Balance for your in-progress orders')
            ->color('warning');
        return [
            'balance' => $balance
        ];
    }


    public function resellerPendingSum(User $user): int
    {

        return Order::query()
            ->pending()
            ->whereBelongsTo($user, 'reseller')
            ->sum('profit');
    }

    public function wholesalerPendingSum(User $user): int
    {
        return OrderItem::query()
            ->where('status', OrderItemStatus::DeliveredToHub->value)
            ->whereHas('order', function ($query) {
                return $query->pending();
            })
            ->whereBelongsTo($user, 'wholesaler')
            ->sum(DB::raw('wholesaler_price * quantity'));
    }


    public static function canView(): bool
    {
        /** @var App\Models\User $user */
        $user = auth()->user();
        return  $user->isBusiness();
    }
}
