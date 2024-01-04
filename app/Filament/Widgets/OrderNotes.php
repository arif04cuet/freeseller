<?php

namespace App\Filament\Widgets;

use App\Enum\OrderClaimStatus;
use App\Enum\OrderStatus;
use App\Enum\TransactionMetaText;
use App\Models\Order;
use App\Models\OrderClaim;
use App\Models\OrderItem;
use App\Models\User;
use DB;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

use function App\Helpers\floatFn;

class OrderNotes extends BaseWidget
{
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 2;
    protected static ?string $heading = 'Order Pending Notes';

    public static function canView(): bool
    {
        return auth()->user()->isHubManager() || auth()->user()->isSuperAdmin();
    }


    public function table(Table $table): Table
    {
        $wholesaler = auth()->user()->id;

        return $table
            ->deferLoading()
            ->query(
                Order::query()
                    ->whereJsonContains('notes', ['status' => "pending"])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order#')
                    ->formatStateUsing(fn ($state) => '<u>' . $state . '</u>')
                    ->html()
                    ->action(
                        Tables\Actions\Action::make('notes')
                            ->label('Show Notes')
                            ->icon('heroicon-o-bars-4')
                            ->iconButton()
                            ->modalSubmitActionLabel('Approve')
                            ->modalHeading(fn (Model $record) => 'Notes for order#' . $record->id . ' (' . $record->customer->name . ')')
                            ->modalContent(fn (Model $record) => view('order.notes', compact('record')))
                            ->action(function (Model $record) {
                                $notes = collect($record->notes)
                                    ->map(function ($note) {
                                        $note['status'] = 'approved';
                                        return $note;
                                    })->toArray();

                                $record->update(['notes' => $notes]);

                                Notification::make()
                                    ->success()
                                    ->title('Note Approve.')
                                    ->send();

                                User::sendMessage(
                                    users: $record->reseller,
                                    title: 'Note has been approved for order =' . $record->id,
                                    url: route('filament.app.resources.orders.index', ['tableSearch' => $record->id])
                                );
                            }),
                    ),
                Tables\Columns\TextColumn::make('reseller.business.name')
                    ->label('Reseller')
            ]);
    }
}
