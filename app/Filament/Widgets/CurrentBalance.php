<?php

namespace App\Filament\Widgets;

use App\Enum\OrderItemStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CurrentBalance extends BaseWidget implements HasForms, HasActions
{

    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'default' => 2,
        'md' => 1,
    ];
    protected function getColumns(): int
    {
        return 1;
    }

    protected function getStats(): array
    {
        /** @var App\Models\User $user */
        $user = auth()->user();

        $balance = $user->active_balance;

        // if ($user->isReseller()) {

        //     //minus lock amount from balance
        //     $lockAmount = auth()->user()->lock_amount_sum;
        //     $balance = $balance - $lockAmount;
        // }


        //
        $msg = 'Balance you can withdraw';
        $color = 'success';
        if ($user->isReseller() && $balance < config('freeseller.minimum_acount_balance')) {
            $msg = new HtmlString(
                'Your current balance is less than ' . config('freeseller.minimum_acount_balance') .
                    ' <a class="bg-red-500 font-bold rounded" href="' .
                    route('filament.app.resources.wallet-recharge-requests.index') . '">Recharge</a>'
            );
            $color = 'danger';
        }

        $label = new HtmlString(
            view('filament.widgets.current-balance', ['action' => $this->listAction()])->render()
        );

        return [
            Stat::make($label, $balance)
                ->description($msg)
                ->color($color)
        ];
    }

    public function listAction(): Action
    {
        return Action::make('list')
            ->label('Details')
            ->icon('heroicon-o-eye')
            ->color('success')
            ->iconButton()
            ->modalHeading('Available Balance')
            ->modalContent(view('transaction.available-balance'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->action(fn () => dd('ok'));
    }

    public static function canView(): bool
    {
        /** @var App\Models\User $user */
        $user = auth()->user();

        return $user->isSuperAdmin() || $user->isWholesaler() || $user->isReseller();
    }
}
