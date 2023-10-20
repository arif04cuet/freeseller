<?php

namespace App\Filament\Resources;

use App\Enum\PaymentChannel;
use App\Enum\WalletRechargeRequestStatus;
use App\Filament\Resources\WalletRechargeRequestResource\Pages;
use App\Filament\Resources\WalletRechargeRequestResource\Widgets\WhereToPayment;
use App\Models\User;
use App\Models\WalletRechargeRequest;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

class WalletRechargeRequestResource extends Resource
{
    protected static ?string $model = WalletRechargeRequest::class;

    protected static ?string $navigationGroup = 'Reseller';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Recharge Request';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->mine()->latest();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->required()
                    //->mask(fn (Forms\Components\TextInput\Mask $mask) => $mask->money(prefix: 'BDT', thousandsSeparator: ',', decimalPlaces: 0))
                    ->numeric(),
                Forms\Components\Select::make('bank')
                    ->label('Payment Channel')
                    ->required()
                    ->options(PaymentChannel::array()),

                Forms\Components\TextInput::make('tnx_id')
                    ->unique(modifyRuleUsing: function (Unique $rule, callable $get) {
                        return $rule
                            ->where('tnx_id', $get('tnx_id'))
                            ->where('user_id', auth()->user()->id);
                    }, ignoreRecord: true)
                    ->required(),

                SpatieMediaLibraryFileUpload::make('image')
                    ->required()
                    ->label('Tnx Receipt')
                    ->image()
                    ->collection('recharge'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->since(),
                Tables\Columns\TextColumn::make('user.business.name'),
                Tables\Columns\TextColumn::make('amount'),
                Tables\Columns\TextColumn::make('bank')->label('Payment Channel'),
                Tables\Columns\TextColumn::make('tnx_id')->searchable(),
                SpatieMediaLibraryImageColumn::make('image')
                    ->label('recept')
                    ->action(
                        Tables\Actions\Action::make('View Image')
                            ->action(function (Model $record): void {
                            })
                            ->modalActions([])
                            ->modalContent(
                                fn (Model $record) => view('products.single-image', [
                                    'url' => $record->getMedia('recharge')->first()->getUrl(),
                                ])
                            ),
                    )
                    ->collection('recharge'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                // Tables\Columns\TextColumn::make('action_taken_at')
                //     ->dateTime(),

            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (Model $record) => $record->status == WalletRechargeRequestStatus::Pending && $record->user_id == auth()->user()->id
                    ),
                Tables\Actions\Action::make('approved')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->iconButton()
                    ->visible(fn (Model $record) => ($record->status == WalletRechargeRequestStatus::Pending) && auth()->user()->isSuperAdmin())
                    ->requiresConfirmation()
                    ->action(
                        function (Model $record) {
                            $record->markAsApproved();
                            User::sendMessage(
                                users: $record->user,
                                title: 'Your recharge has been approved check your balance. tnx=' . $record->tnx_id,
                                url: route('filament.app.resources.wallet-recharge-requests.index', ['tableSearch' => $record->id])
                            );
                        }
                    ),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWalletRechargeRequests::route('/'),
        ];
    }
    public static function getWidgets(): array
    {
        return [
            WhereToPayment::class
        ];
    }
}
