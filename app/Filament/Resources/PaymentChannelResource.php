<?php

namespace App\Filament\Resources;

use App\Enum\PaymentChannel as EnumPaymentChannel;
use App\Filament\Resources\PaymentChannelResource\Pages;
use App\Models\PaymentChannel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentChannelResource extends Resource
{
    protected static ?string $model = PaymentChannel::class;

    protected static ?string $modelLabel = 'Account';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereBelongsTo(auth()->user());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options(EnumPaymentChannel::array())
                    ->reactive()
                    ->required(),
                Forms\Components\TextInput::make('mobile_no')
                    ->label('bKash Mobile No')
                    ->type('number')
                    ->rules('numeric|digits_between:11,11')
                    ->placeholder('01xxxxxxxxx')
                    //->unique(ignoreRecord: true)
                    ->required()
                    ->hintAction(
                        Forms\Components\Actions\Action::make('copyMyMobile')
                            ->icon('heroicon-m-clipboard')
                            ->action(function (Set $set, $state) {
                                $mobile = auth()->user()->mobile;
                                $set('mobile_no', $mobile);
                            })
                    )
                    ->visible(fn (\Filament\Forms\Get $get) => $get('type') == EnumPaymentChannel::bKash->value),
                Forms\Components\Grid::make('bank')
                    ->visible(fn (\Filament\Forms\Get $get) => $get('type') == EnumPaymentChannel::Bank->value)
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->required(),
                        Forms\Components\TextInput::make('bank_routing_no')
                            ->required(),
                        Forms\Components\TextInput::make('account_name')
                            ->required(),
                        Forms\Components\TextInput::make('account_number')
                            ->required(),
                        Forms\Components\TextInput::make('branch_name')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('mobile_no'),
                Tables\Columns\TextColumn::make('bank_name'),
                Tables\Columns\TextColumn::make('bank_routing_no'),
                Tables\Columns\TextColumn::make('account_name'),
                Tables\Columns\TextColumn::make('account_number'),
                Tables\Columns\TextColumn::make('branch_name'),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                //Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                //Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePaymentChannels::route('/'),
        ];
    }
}
