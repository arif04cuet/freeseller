<?php

namespace App\Livewire;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class OrderItemsView extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public array $itemIds;

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->query(
                OrderItem::query()
                    ->with(['wholesaler', 'wholesaler.business'])
                    ->whereIn('id', $this->itemIds)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->action(
                        Tables\Actions\Action::make('View Image')
                            ->action(function (Model $record): void {
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(fn (Model $record) => view(
                                'products.gallery',
                                [
                                    'medias' => $record->sku->getMedia('sharees'),
                                ]
                            )),
                    )
                    ->defaultImageUrl(
                        fn (Model $record) => $record->sku->getMedia('sharees')->first()->getUrl('thumb')
                    ),
                Tables\Columns\TextColumn::make('sku.name')
                    ->label('Product Name')
                    ->html()
                    ->formatStateUsing(fn (string $state) => '<u>' . $state . '</u>')
                    ->action(
                        Tables\Actions\Action::make('View Details')
                            ->label('')
                            ->action(function (Model $record): void {
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalHeading('Product Details')
                            ->modalContent(fn (Model $record) => view(
                                'products.single-sku',
                                [
                                    'sku' => $record->sku,
                                ]
                            )),
                    ),
                Tables\Columns\TextColumn::make('sku.price')
                    ->label('Price (Taka)'),
                Tables\Columns\TextColumn::make('quantity')
                    ->formatStateUsing(
                        fn (Model $record, $state) => auth()->user()->isWholesaler() ?
                            $state . ' (' . $record->sku->quantity . ')' : $state
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->wrap()
                    ->html()
                    ->formatStateUsing(
                        function (Model $record) {

                            $status = $record->status->name;

                            if ($record->is_returned_to_wholesaler)
                                $status .= '<br/><span class="text-primary-600"> (sent to wholesaler)</span>';

                            return $status;
                        }
                    ),
                Tables\Columns\TextColumn::make('order.note_for_wholesaler')
                    ->label('Reseller Note')
                    ->wrap()
                    ->visible(fn () => auth()->user()->isWholesaler()),
                Tables\Columns\TextColumn::make('wholesaler')
                    ->html()
                    ->visible(fn () => auth()->user()->isHubManager())
                    ->formatStateUsing(
                        function (Model $record) {

                            $wholesaler = $record->wholesaler;

                            return '<a href="tel:' . $wholesaler->mobile . '">' .
                                $wholesaler->business->name . '<br/>' .
                                $wholesaler->name .
                                '</a>';
                        }
                    )
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $record->status == OrderItemStatus::Returned && !$record->is_returned_to_wholesaler,
            )
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('send_otp')
                        ->visible(fn () => auth()->user()->isHubManager())
                        ->icon('heroicon-o-envelope')
                        ->deselectRecordsAfterCompletion()
                        ->modalHeading('OTP has been sent to wholesaler.')
                        // ->fillForm(fn (Post $record): array => [
                        //     'title' => $record->title,
                        //     'content' => $record->content,
                        // ])
                        ->fillForm(
                            function (Collection $records): array {

                                $otp = random_int(100000, 999999);
                                $item = $records->first();

                                Notification::make()
                                    ->success()
                                    ->title('OTP sent to wholesaler')
                                    ->send();

                                User::sendMessage(
                                    users: $item->wholesaler,
                                    title: 'OTP to collect return product for order=' . $item->order->id,
                                    body: 'OTP : ' . $otp,
                                    actions: [
                                        Action::make('read')
                                            ->button()
                                            ->markAsRead()
                                    ]
                                );

                                return [
                                    'sent_otp' => $otp
                                ];
                            }
                        )
                        ->form([
                            Forms\Components\TextInput::make('entered_otp')
                                ->label('Enter OTP to verify')
                                ->required()
                                ->numeric(),
                            Forms\Components\TextInput::make('sent_otp')
                                ->hidden()
                        ])
                        ->action(
                            function (Collection $records, Form $form, Tables\Actions\BulkAction $action) {

                                $data = $form->getRawState();

                                if ($data['entered_otp'] != $data['sent_otp']) {

                                    Notification::make()
                                        ->danger()
                                        ->title('OTP mismatched!')
                                        ->persistent()
                                        ->send();

                                    $action->halt();
                                }


                                OrderItem::query()
                                    ->whereIn('id', $records->pluck('id')->toArray())
                                    ->update([
                                        'is_returned_to_wholesaler' => true
                                    ]);

                                Notification::make()
                                    ->success()
                                    ->title('Success!')
                                    ->persistent()
                                    ->send();
                            }
                        )
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.order-items-view');
    }
}
