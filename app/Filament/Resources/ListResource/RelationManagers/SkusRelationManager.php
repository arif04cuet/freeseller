<?php

namespace App\Filament\Resources\ListResource\RelationManagers;

use App\Jobs\AddWaterMarkToMedia;
use App\Jobs\ZipAndSendToReseller;
use App\Models\AttributeValue;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Arr;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Intervention\Image\Facades\Image;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class SkusRelationManager extends RelationManager
{
    protected static string $relationship = 'skus';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query) => $query
                    ->with([
                        'product',
                        'product.category' => fn ($q) => $q->select('id', 'name'),
                        'media'
                    ])
                    ->select(['skus.id', 'sku_id', 'name', 'product_id', 'quantity'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('sku_id'),
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('sharees')
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->action(
                        Tables\Actions\Action::make('View Image')
                            ->action(function (Model $record): void {
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(fn (Model $record) => view(
                                'products.gallery',
                                [
                                    'medias' => $record->getMedia('sharees'),
                                ]
                            )),
                    )
                    ->conversion('thumb'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->html()
                    ->formatStateUsing(fn (Model $record) => $record->quantity ? $record->name : '<span class="text-primary-600">' . $record->name . '</span>'),
                Tables\Columns\TextColumn::make('product.price')
                    ->label('Price')
                    ->formatStateUsing(fn (Model $record, $state) => '<div class="fi-ta-text grid gap-y-1 px-3">
                    <div>' . $record->product->price . '</div>
                    <div><del>' . ($record->product->getOfferPrice() ? $record->product->getAttributes()['price'] : '') . '</del></div>
                </div>
                ')->html(),

                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('product.category.name'),

                // SpatieMediaLibraryImageColumn::make('image')
                //     ->collection('sharees')
                //     ->action(
                //         Tables\Actions\Action::make('View Image')
                //             ->action(function (Product $record): void {
                //             })
                //             ->modalActions([])
                //             ->modalContent(fn (Model $record) => view('products.gallery', compact('record'))),
                //     )
                //     ->conversion('thumb'),
                // // Tables\Columns\TextColumn::make('productType.name'),
                // Tables\Columns\TextColumn::make('name')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('product.price')
                //     ->formatStateUsing(fn ($state) => (int) $state),
                // // Tables\Columns\TagsColumn::make('quantity')
                // //     ->label('Color wise quantity')
                // //     ->getStateUsing(
                //         fn (Model $record) => $record->getQuantities()
                //             ->map(fn ($item) => $item['color'] . ' = ' . $item['quantity'])
                //             ->toArray()
                //     ),
                //,
                // Tables\Columns\TextColumn::make('skus_sum_quantity')
                //     ->label('Total')
                //     ->sum('skus', 'quantity'),
                //Tables\Columns\TextColumn::make('category.name'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business')
                    ->label('Business')
                    ->searchable()
                    ->preload()
                    ->relationship('product.owner', 'name', fn (Builder $query) => $query->with(['roles'])->wholesalers())
                    ->getOptionLabelFromRecordUsing(
                        fn (Model $record) => $record->id_number
                    )
                    ->indicateUsing(function (array $data): ?string {

                        if (empty($data['value'])) {
                            return null;
                        }
                        return 'Business :' . User::query()->find($data['value'])->id_number;
                    }),
                Tables\Filters\SelectFilter::make('category')
                    ->searchable()
                    ->preload()
                    ->options(Category::query()->pluck('name', 'id'))
                    ->query(
                        function (Builder $query, array $data): Builder {
                            return $query->when(
                                $data['value'],
                                fn ($q) => $q->whereHas(
                                    'product',
                                    fn ($q) => $q->where('category_id', $data['value'])
                                )
                            );
                        }
                    ),

                Tables\Filters\SelectFilter::make('color')
                    ->searchable()
                    ->options(AttributeValue::whereHas('attribute', function ($query) {
                        return $query->whereName('Color');
                    })->pluck('label', 'id'))
                    ->query(function (Builder $query, array $data) {
                        $query->when($data['value'], function ($q) use ($data) {
                            return $q->whereHas('attributeValues', function ($q) use ($data) {
                                return $q->where('attribute_value_id', $data['value']);
                            });
                        });
                    }),

                Tables\Filters\Filter::make('price')
                    ->form([
                        Forms\Components\Grid::make('price_range')
                            ->columns([
                                'default' => 2,
                                'md' => 2
                            ])
                            ->schema([

                                Forms\Components\TextInput::make('from')->label('Price From')->numeric()
                                    ->columns(1),
                                Forms\Components\TextInput::make('to')->numeric()
                                    ->columns(1),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        //logger($data);
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $from): Builder => $query->whereHas('product', fn ($q) => $q->where('price', '>=', $data['from'])),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $to): Builder => $query->whereHas('product', fn ($q) => $q->where('price', '<=', $data['to'])),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),

            ])
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $record->quantity > 0,
            )
            ->bulkActions([
                Tables\Actions\ActionGroup::make([
                    // Tables\Actions\BulkAction::make('create_order')
                    //     ->label('Create New Order')
                    //     ->icon('heroicon-o-plus')
                    //     ->color('success')
                    //     ->requiresConfirmation()
                    //     ->modalSubmitAction(fn (StaticAction $action) => $action->extraAttributes([
                    //         'wire:loading.remove' => 'wire:loading.remove'
                    //     ]))
                    //     ->modalDescription(
                    //         function (Collection $records) {
                    //             return new HtmlString('
                    //             <p>Total Items = ' . $records->count() . '</p>
                    //             <hr class="my-4"/>
                    //             <ol class="list-decimal mx-auto text-left">
                    //             ' . $records->map(fn ($sku, $index) => '<li>' . $index + 1 . '. ' . $sku->name . '</li>')->implode('') . '
                    //             </ol>
                    //             <div wire:loading class="text-custom-600 mt-2">Working Wait ...</div>
                    //             ');
                    //         }
                    //     )
                    //     ->modalCancelAction(false)
                    //     ->modalSubmitActionLabel('Yes, Create')
                    //     ->action(
                    //         function (RelationManager $livewire, Collection $records, array $data) {
                    //             $data = [];
                    //             $data['list'] = $livewire->getOwnerRecord()->id;

                    //             $data['skus'] = $records
                    //                 ->map(fn ($item) => $item->product->id . '-' . $item->id)
                    //                 ->implode(',');

                    //             //$qString = Arr::query($data);
                    //             return redirect()->route('filament.app.resources.orders.create', $data);
                    //         }
                    //     ),
                    Tables\Actions\DetachBulkAction::make(),
                    Tables\Actions\BulkAction::make('export_images')
                        ->label('Download Images')
                        ->modalSubmitActionLabel('Download')
                        ->icon('heroicon-o-arrow-down-tray')
                        // ->form([
                        //     Forms\Components\Select::make('watermark_position')
                        //         ->label('Watermark Position')
                        //         ->required()
                        //         ->options([
                        //             'top_left' => 'Top Left',
                        //             'top_right' => 'Top Right',
                        //             'bottom_left' => 'Bottom Left',
                        //             'bottom_right' => 'Bootom Right',
                        //         ]),
                        // ])
                        ->action(
                            function (Collection $records, array $data) {

                                $urls = [];

                                foreach ($records as $sku) {

                                    $images = $sku->getMedia('sharees');

                                    if ($images->count() == 0) {
                                        continue;
                                    }

                                    foreach ($sku->getMedia('sharees') as $media) {
                                        $urls[] = $media->getPath();
                                    }
                                }

                                $user = auth()->user();
                                ZipAndSendToReseller::dispatch($user, $urls);

                                Notification::make()
                                    ->title('Success. your download will be avilable after a minute in notification sections')
                                    ->success()
                                    ->send();
                            }
                        ),
                    ExportBulkAction::make()
                        ->label('Export Info')
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withColumns([
                                    Column::make('name'),
                                    Column::make('product.description')
                                        ->formatStateUsing(fn ($state) => strip_tags($state)),
                                    Column::make('product.price'),
                                    Column::make('quantity'),
                                    Column::make('product.category.name'),

                                ]),
                        ]),
                ]),


            ]);
    }

    public static function imageXY($img, $data): array
    {
        $pad = 10;
        switch ($data['watermark_position']) {
            case 'top_left':
                $x = $y = $pad;
                break;
            case 'top_right':
                $x = $img->width() - ($pad + 10);
                $y = $pad;
                break;

            case 'bottom_left':
                $x = $pad;
                $y = $img->height() - $pad;
                break;

            case 'bottom_right':
                $x = $img->width() - ($pad + 10);
                $y = $img->height() - $pad;
                break;
            default:
                $x = $y = $pad;
                break;
        }

        return [$x, $y];
    }
    function zip_files($source, $destination)
    {
        $zip = new ZipArchive();
        if ($zip->open($destination, ZIPARCHIVE::CREATE) === true) {
            $source = realpath($source);

            if (is_dir($source)) {
                $iterator = new RecursiveDirectoryIterator($source);
                $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);

                foreach ($files as $file) {
                    $file = realpath($file);

                    if (is_dir($file)) {
                        $zip->addEmptyDir(str_replace($source . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR));
                    } else if (is_file($file)) {
                        $zip->addFile($file, str_replace($source . DIRECTORY_SEPARATOR, '', $file));
                    }
                }
            } else if (is_file($source)) {
                $zip->addFile($source, basename($source));
            }
        }

        return $zip->close();
    }
}
