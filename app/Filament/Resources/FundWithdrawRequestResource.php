<?php

namespace App\Filament\Resources;

use App\Enum\PaymentChannel;
use App\Enum\WalletRechargeRequestStatus;
use App\Filament\Resources\FundWithdrawRequestResource\Pages;
use App\Filament\Resources\FundWithdrawRequestResource\RelationManagers;
use App\Models\FundWithdrawRequest;
use App\Models\PaymentChannel as ModelsPaymentChannel;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ->helperText(fn () => 'Available Balance: ' . auth()->user()->balance)
                    ->maxValue(auth()->user()->balance)
                    ->rules('max:' . auth()->user()->balance)
                    ->required()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name'),
                Tables\Columns\TextColumn::make('amount'),
                Tables\Columns\TextColumn::make('status')
                    ->colors([
                        'warning' => WalletRechargeRequestStatus::Pending->value,
                        'success' => WalletRechargeRequestStatus::Approved->value,
                    ]),
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
                    ),

                Tables\Actions\Action::make('approved')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->iconButton()
                    ->visible(fn (Model $record) => ($record->status == WalletRechargeRequestStatus::Pending) && auth()->user()->isSuperAdmin())
                    ->requiresConfirmation()
                    ->action(fn (Model $record) => $record->markAsApproved())
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFundWithdrawRequests::route('/'),
        ];
    }
}
