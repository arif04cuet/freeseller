<?php

namespace App\Filament\Resources;

use App\Enum\PaymentChannel;
use App\Enum\WalletRechargeRequestStatus;
use App\Filament\Resources\FundWithdrawRequestResource\Pages;
use App\Models\FundWithdrawRequest;
use App\Models\PaymentChannel as ModelsPaymentChannel;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class FundWithdrawRequestResource extends Resource
{
    protected static ?string $model = FundWithdrawRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()
            ->when(
                auth()->user()->isSuperAdmin(),
                fn ($q) => $q->where('status', WalletRechargeRequestStatus::Pending->value)
            )
            ->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(!auth()->user()->isSuperAdmin(), function ($query) {
                $query->whereBelongsTo(auth()->user());
            })
            ->latest();
    }

    public static function form(Form $form): Form
    {
        $minimumBalance = self::minimumBalance();

        return $form
            ->schema([

                Forms\Components\Select::make('type')
                    ->label('Channel')
                    ->options(PaymentChannel::array())
                    ->live()
                    ->dehydrated(false)
                    // ->afterStateHydrated(
                    //     fn (?Model $record, $component) => $record && $component->state($record->paymentChannel->type->value)
                    // )
                    ->required(),

                Forms\Components\Select::make('payment_channel_id')
                    ->label('Account')
                    ->options(fn (\Filament\Forms\Get $get) => ModelsPaymentChannel::list($get('type')))
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->helperText(
                        function (?Model $record, $operation) use ($minimumBalance) {
                            $limit = (int) ($operation == 'edit' ?
                                (auth()->user()->active_balance + $record->lockAmount->amount - $minimumBalance) : (auth()->user()->active_balance - $minimumBalance)
                            );
                            $limit = $limit > 0 ? $limit : 0;

                            return new HtmlString(
                                'Available Balance: <b>' . auth()->user()->active_balance . '</b> | ' .
                                    'Max withdrwable amount: <b>' . $limit . '</b>'
                            );
                        }
                    )
                    ->maxValue(
                        fn (?Model $record, $operation) => (int) ($operation == 'edit' ?
                            (auth()->user()->active_balance + $record->lockAmount->amount - $minimumBalance) : (auth()->user()->active_balance - $minimumBalance)
                        )
                    )
                    ->minValue(100)
                    ->required(),

                Forms\Components\Select::make('fund_transfer_fee')
                    ->label('Transfer Mode')
                    ->default(1)
                    ->live()
                    ->dehydrateStateUsing(
                        fn ($state) => $state == 1 ? config('freeseller.fund_transfer_fee') : 0
                    )
                    ->afterStateHydrated(
                        fn (?Model $record, $component) => $record && $component->state(1)
                    )
                    ->required()
                    ->options(
                        fn () => [
                            1 => 'Same Day ( Charge ' . config('freeseller.fund_transfer_fee') . ' Tk )',
                            2 => 'Next Day ( Charge 0 Tk )',
                        ]
                    )


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.business.name')
                    ->html()
                    ->formatStateUsing(
                        fn (Model $record) => $record->user->business->name . '- ' . $record->user->id_number . '<br/>' .
                            $record->user->name
                    ),
                //Tables\Columns\TextColumn::make('user.name')->label('Owner'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->summarize(
                        Sum::make()
                            ->label('Total Withdrawn')
                            ->query(fn ($query) => $query->where('status', WalletRechargeRequestStatus::Approved->value))
                    ),
                Tables\Columns\TextColumn::make('fund_transfer_fee')
                    ->label('Fee'),
                Tables\Columns\TextColumn::make('paymentChannel.label')
                    ->label('Channel')
                    ->html()
                    ->formatStateUsing(fn ($state) => str_replace('-', '<br/>', $state)),
                Tables\Columns\TextColumn::make('status')
                    ->colors([
                        'warning' => WalletRechargeRequestStatus::Pending->value,
                        'success' => WalletRechargeRequestStatus::Approved->value,
                    ]),
                // SpatieMediaLibraryImageColumn::make('image')
                //     ->label('Transfer recept')
                //     ->action()
                //     ->collection('fund_approved'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(WalletRechargeRequestStatus::class),
                Tables\Filters\Filter::make('approved_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('approved_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('approved_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([


                    Tables\Actions\Action::make('View Receipt')
                        ->icon('heroicon-o-eye')
                        ->visible(fn (Model $record) => $record->status == WalletRechargeRequestStatus::Approved)
                        ->action(function (Model $record): void {
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(
                            fn (Model $record) => view('products.single-image', [
                                'url' => $record->getMedia('fund_approved')->first()->getUrl(),
                            ])
                        ),
                    Tables\Actions\EditAction::make()
                        ->visible(
                            fn (Model $record) => $record->status == WalletRechargeRequestStatus::Pending &&
                                $record->user->is(auth()->user())
                        ),
                    Tables\Actions\DeleteAction::make()
                        ->visible(
                            fn (Model $record) => $record->status == WalletRechargeRequestStatus::Pending &&
                                $record->user->is(auth()->user())
                        )
                        ->using(function (Tables\Actions\DeleteAction $action, Model $record): void {
                            $result = $record->delete();

                            if (!$result) {
                                $action->failure();

                                return;
                            }

                            $record->lockAmount()->exists() && $record->lockAmount()->delete();

                            $action->success();
                        }),

                    Tables\Actions\Action::make('approved')
                        ->label('Approve')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->visible(fn (Model $record) => ($record->status == WalletRechargeRequestStatus::Pending) && auth()->user()->isSuperAdmin())
                        //->requiresConfirmation()
                        ->fillForm(fn (Model $record): array => [
                            'amount' => $record->amount,
                            'lockAmount' => $record->lockAmount->amount,
                            'active_balance' => (int) $record->user->active_balance
                        ])
                        ->form([
                            Forms\Components\Grid::make()
                                ->columns(3)
                                ->disabled()
                                ->schema([
                                    Forms\Components\TextInput::make('amount')
                                        ->label('Requested Amount'),
                                    Forms\Components\TextInput::make('lockAmount')
                                        ->label('Lock Amount'),
                                    Forms\Components\TextInput::make('active_balance')
                                ]),

                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\ViewField::make('payment_channel')
                                        ->view('fund.view-payment-channel'),

                                    SpatieMediaLibraryFileUpload::make('image')
                                        ->required()
                                        ->label('Tnx Receipt')
                                        ->image()
                                        ->collection('fund_approved')
                                ]),

                            Forms\Components\TextInput::make('transfer_amount')
                                ->required()
                                ->numeric()
                                ->in(function (Model $record) {
                                    return [$record->amount];
                                })
                                ->label('Transfer Amount'),


                        ])
                        ->action(fn (Model $record) => $record->markAsApproved()),
                ])
            ])
            ->bulkActions([
                //Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFundWithdrawRequests::route('/'),
        ];
    }

    public static function minimumBalance(): int
    {
        if (auth()->user()->isWholesaler())
            return 0;

        return config('freeseller.minimum_acount_balance') ?: 1000;
    }
}
