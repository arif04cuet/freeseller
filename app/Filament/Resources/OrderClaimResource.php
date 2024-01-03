<?php

namespace App\Filament\Resources;

use App\Enum\OrderClaimStatus;
use App\Enum\OrderClaimType;
use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\OrderClaimResource\Pages;
use App\Filament\Resources\OrderClaimResource\RelationManagers;
use App\Models\Order;
use App\Models\OrderClaim;
use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderClaimResource extends Resource
{
    protected static ?string $model = OrderClaim::class;

    protected static ?int $navigationSort = 5;
    protected static ?string $navigationGroup = 'Reseller';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole([
            SystemRole::Reseller->value,
            'super_admin',
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('order')
            ->whereHas(
                'order',
                fn ($q) => $q->whereBelongsTo(auth()->user(), 'reseller')
            )->latest();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->required()
                    ->label('Select Order')
                    ->searchable()
                    ->placeholder('Search by Order#')
                    ->loadingMessage('Searching...')
                    ->noSearchResultsMessage('No order found. please sellect only returned and cancelled order')
                    ->searchDebounce(500)
                    ->getSearchResultsUsing(
                        fn (string $search): array => Order::query()
                            ->mine()
                            ->whereNotNull('delivered_at')
                            ->whereIn('status', [
                                OrderStatus::Cancelled->value,
                                OrderStatus::Partial_Delivered->value,
                            ])
                            ->doesntHave('claim')
                            ->where('id', $search)
                            ->pluck('id', 'id')
                            ->toArray()
                    )
                    ->getOptionLabelUsing(fn ($value): ?string => $value),
                Forms\Components\Grid::make()
                    ->visible(fn (Get $get) => filled($get('order_id')))
                    ->columns(1)
                    ->schema([

                        Forms\Components\Select::make('type')
                            ->required()
                            ->label('Claim For')
                            ->default(1)
                            ->options(OrderClaimType::array()),
                        Forms\Components\Repeater::make('order_items')
                            ->reorderable(false)
                            ->schema([
                                forms\Components\Grid::make()
                                    ->schema([

                                        Forms\Components\Select::make('item_id')
                                            ->label('Select Item')
                                            ->live()
                                            ->afterStateUpdated(
                                                function ($state, $set) {
                                                    $wholesaler = OrderItem::find($state)->wholesaler_id;
                                                    $set('wholesaler', "$wholesaler");
                                                }
                                            )
                                            ->preload()
                                            ->allowHtml()
                                            ->searchable()
                                            ->options(
                                                function (Get $get) {
                                                    $orderId = $get('../../order_id');
                                                    return  OrderItem::query()
                                                        ->with('sku')
                                                        ->where('order_id', $orderId)
                                                        ->whereIn('status', [
                                                            OrderItemStatus::Returned->value,
                                                            OrderItemStatus::Returned->value,
                                                        ])
                                                        ->get()
                                                        ->map(
                                                            function ($item) {
                                                                $sku = $item->sku;
                                                                return [
                                                                    'id' => $item->id,
                                                                    'name' => '<div class="flex gap-2">
                                        <img src="' . $sku->getMedia('*')->first()->getUrl('thumb') . '"/>
                                        <span>' . $sku->name . '</span>
                                    </div>'
                                                                ];
                                                            }
                                                        )
                                                        ->pluck('name', 'id');
                                                }
                                            ),

                                        SpatieMediaLibraryFileUpload::make('images')
                                            ->label('Images from cutomer')
                                            ->multiple()
                                            ->required()
                                            ->panelLayout('grid')
                                            ->image()
                                            ->maxFiles(5)
                                            ->visible(fn ($get) => filled($get('item_id')))
                                            ->collection(fn ($get) => 'claims-' . $get('item_id')),

                                        Forms\Components\Hidden::make('wholesaler')

                                    ])
                            ]),
                        forms\Components\Textarea::make('reseller_comment')
                            ->required()
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable(),
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order#'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('reseller_comment')
                    ->wrap(),
                Tables\Columns\TextColumn::make('wholesaler_comment')
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Model $record) => $record->status = OrderClaimStatus::Pending),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ])
            ->emptyStateActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderClaims::route('/'),
            'create' => Pages\CreateOrderClaim::route('/create'),
            'edit' => Pages\EditOrderClaim::route('/{record}/edit'),
        ];
    }

    public static function addWhoesalers(array $data): array
    {
        $items = collect($data['order_items'])->pluck('item_id')->toArray();

        $data['wholesalers'] = OrderItem::query()
            ->select('wholesaler_id')
            ->whereIn('id', $items)
            ->pluck('wholesaler_id')
            ->unique()
            ->map(fn ($wholesaler) => [
                'id' => "$wholesaler",
                'paid' => "0"
            ])
            ->toArray();

        return $data;
    }
}
