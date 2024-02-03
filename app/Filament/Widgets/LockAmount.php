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

class LockAmount extends Widget implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use CanPoll;



    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = [
        'default' => 2,
        'md' => 1,
    ];

    protected static string $view = 'filament.widgets.lock-amount';

    protected function getPollingInterval(): ?string
    {
        return null;
    }

    protected function getColumns(): int
    {
        return 1;
    }

    public function listAction(): Action
    {
        return Action::make('list')
            ->label('Details')
            ->icon('heroicon-o-eye')
            ->iconButton()
            ->modalHeading('Lock Amount')
            ->modalContent(view('transaction.lock-amount'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->action(fn () => dd('ok'));
    }

    protected function getViewData(): array
    {

        $balance = Stat::make(
            new HtmlString('Lock Amount (TK)'),
            auth()->user()->lockAmount()->sum('amount')
        )
            ->description('Balance currently on lock')
            ->color('warning');

        return [
            'balance' => $balance
        ];
    }

    public static function canView(): bool
    {
        /** @var App\Models\User $user */
        $user = auth()->user();
        return $user->isBusiness();
    }
}
