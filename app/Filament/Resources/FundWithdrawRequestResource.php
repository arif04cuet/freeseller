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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class FundWithdrawRequestResource extends Resource
{
    protected static ?string $model = FundWithdrawRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                    ->reactive()
                    ->dehydrated(false)
                    ->required(),

                Forms\Components\Select::make('payment_channel_id')
                    ->label('Account')
                    ->options(fn (\Filament\Forms\Get $get) => ModelsPaymentChannel::list($get('type')))
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->helperText(
                        function () use ($minimumBalance) {
                            $limit = (int) auth()->user()->active_balance - $minimumBalance;
                            $limit = $limit > 0 ? $limit : 0;

                            return new HtmlString(
                                'Available Balance: <b>' . auth()->user()->active_balance . '</b> | ' .
                                    'Max withdrwable amount: <b>' . $limit . '</b>'
                            );
                        }
                    )
                    ->maxValue(
                        fn () => (int) (auth()->user()->active_balance - $minimumBalance)
                    )
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.business.name'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Requested Amount'),
                Tables\Columns\TextColumn::make('paymentChannel.type')
                    ->label('Channel'),
                Tables\Columns\TextColumn::make('status')
                    ->colors([
                        'warning' => WalletRechargeRequestStatus::Pending->value,
                        'success' => WalletRechargeRequestStatus::Approved->value,
                    ]),
                SpatieMediaLibraryImageColumn::make('image')
                    ->label('Transfer recept')
                    ->action(
                        Tables\Actions\Action::make('View Image')
                            ->action(function (Model $record): void {
                            })
                            ->modalActions([])
                            ->modalContent(
                                fn (Model $record) => view('products.single-image', [
                                    'url' => $record->getMedia('fund_approved')->first()->getUrl(),
                                ])
                            ),
                    )
                    ->collection('fund_approved'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
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
                    ->iconButton()
                    ->visible(fn (Model $record) => ($record->status == WalletRechargeRequestStatus::Pending) && auth()->user()->isSuperAdmin())
                    //->requiresConfirmation()
                    ->fillForm(fn (Model $record): array => [
                        'amount' => $record->amount,
                        'lockAmount' => $record->lockAmount->amount,
                        'active_balance' => $record->user->active_balance
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
                        Forms\Components\ViewField::make('payment_channel')
                            ->columnSpanFull()
                            ->view('fund.view-payment-channel'),

                        SpatieMediaLibraryFileUpload::make('image')
                            ->required()
                            ->label('Tnx Receipt')
                            ->image()
                            ->collection('fund_approved'),

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
