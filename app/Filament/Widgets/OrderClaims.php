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
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

use function App\Helpers\floatFn;

class OrderClaims extends BaseWidget
{
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 2;
    protected static ?string $heading = 'Order Claims';
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return !auth()->user()->isReseller();
    }


    public function table(Table $table): Table
    {
        $wholesaler = auth()->user()->id;

        return $table
            ->deferLoading()
            ->query(
                OrderClaim::query()
                    ->whereJsonContains('wholesalers', ['id' => "$wholesaler"])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order#')
                    ->formatStateUsing(fn ($state) => '<u>' . $state . '</u>')
                    ->html()
                    ->action(
                        Tables\Actions\Action::make('claims')
                            ->label('Claims')
                            ->icon('heroicon-o-bars-4')
                            ->iconButton()
                            ->action(function (Model $record) {
                                DB::transaction(function () use ($record) {

                                    $wholesaler = auth()->user();
                                    $order = $record->loadMissing('order.reseller')->order;

                                    //update claim
                                    $wholesalers = collect($record->wholesalers)
                                        ->map(function ($item) use ($wholesaler) {

                                            if ($item['id'] == $wholesaler->id)
                                                $item['paid'] = "1";

                                            return $item;
                                        });

                                    $record->update([
                                        'wholesalers' => $wholesalers->toArray(),
                                        'status' => OrderClaimStatus::Approved->value
                                    ]);

                                    // //transfer payments
                                    $toUser = $wholesalers->filter(fn ($item) => (bool)$item['paid'])->count() == 1 ?
                                        $order->reseller : User::platformOwner();

                                    $amount = floatFn($order->courier_charge);
                                    $wholesaler->forceTransferFloat($toUser, $amount, [
                                        'description' => TransactionMetaText::ORDER_CLAIM->getLabel($order),
                                        'claim' => $record->id
                                    ]);

                                    //send message
                                    User::sendMessage(
                                        users: $toUser,
                                        title: TransactionMetaText::ORDER_CLAIM->getLabel($order),
                                        body: 'Order delivery charge ( ' . $order->courier_charge . ' ) has been added to your wallet',
                                        url: route('filament.app.resources.order-claims.index', ['tableSearch' => $record->id]),
                                        sent_email: true
                                    );
                                });
                            })
                            ->modalCancelAction(false)
                            ->modalSubmitAction(
                                fn (Model $record, $action) => collect($record->wholesalers)
                                    ->filter(fn ($item) => ($item['id'] == auth()->user()->id) && !$item['paid'])
                                    ->count() ? $action : false
                            )
                            ->modalSubmitActionLabel('Accept')
                            ->modalHeading('Claim details')
                            ->modalContent(fn (Model $record) => view('orders.claim', [
                                'claim' => $record->loadMissing('order'),
                                'items' => collect($record->order_items)
                                    ->filter(fn ($item) => $item['wholesaler'] == auth()->user()->id)
                                    ->map(
                                        function ($item) {
                                            $item['sku'] = OrderItem::find($item['item_id'])->sku;
                                            return $item;
                                        }
                                    )
                            ])),
                    ),
                Tables\Columns\TextColumn::make('type')
                    ->label('Claim For'),
                Tables\Columns\TextColumn::make('order.courier_charge')
                    ->label('Delivery Charge'),
                Tables\Columns\TextColumn::make('id')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        function ($record) use ($wholesaler) {
                            $item = collect($record->wholesalers)->filter(fn ($item) => $item['id'] == $wholesaler)->first();
                            $status =  $item['paid'] ? 'Paid' : 'Pending';
                            return $status;
                        }
                    ),
            ]);
    }
}
