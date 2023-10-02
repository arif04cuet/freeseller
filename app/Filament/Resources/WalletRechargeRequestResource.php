<?php

namespace App\Filament\Resources;

use App\Enum\PaymentChannel;
use App\Enum\WalletRechargeRequestStatus;
use App\Filament\Resources\WalletRechargeRequestResource\Pages;
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

class WalletRechargeRequestResource extends Resource
{
    protected static ?string $model = WalletRechargeRequest::class;

    protected static ?string $navigationGroup = 'Reseller';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Recharge Approval';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->mine();
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
                    ->action(fn (Model $record) => $record->markAsApproved()),
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
}
