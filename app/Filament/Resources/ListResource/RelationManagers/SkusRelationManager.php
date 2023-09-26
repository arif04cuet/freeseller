<?php

namespace App\Filament\Resources\ListResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product.category'))
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('sharees')
                    ->action(
                        Tables\Actions\Action::make('View Image')
                            ->action(function (Model $record): void {
                            })
                            ->modalActions([])
                            ->modalContent(fn (Model $record) => view(
                                'products.gallery',
                                [
                                    'medias' => $record->getMedia("sharees")
                                ]
                            )),
                    )
                    ->conversion('thumb'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => (int) $state),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('product.category.name')

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
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),

            ])
            ->bulkActions([

                Tables\Actions\DetachBulkAction::make(),
                Tables\Actions\BulkAction::make('export_images')
                    ->label('Download Images')
                    ->modalSubmitActionLabel('Download')
                    ->form([
                        Forms\Components\Select::make('watermark_position')
                            ->label('Watermark Position')
                            ->required()
                            ->options([
                                'top_left' => 'Top Left',
                                'top_right' => 'Top Right',
                                'bottom_left' => 'Bottom Left',
                                'bottom_right' => 'Bootom Right',
                            ])
                    ])
                    ->action(
                        function (Collection $records, array $data) {

                            $folderName = uniqid();
                            $tmpDir = 'tmp';
                            $folder = $tmpDir . '/' . $folderName;
                            !File::isDirectory($folder) && File::makeDirectory($folder, 0777, true, true);

                            foreach ($records as $sku) {

                                $images = $sku->getMedia('sharees');

                                if ($images->count() == 0)
                                    continue;


                                foreach ($images as $media) {

                                    $path = $media->getPath();

                                    if (!File::exists($path))
                                        continue;

                                    $img = Image::make($path);
                                    list($x, $y) = self::imageXY($img, $data);
                                    $img->text($sku->waterMarkText(), $x, $y);

                                    $savePath = $folder . '/' . uniqid("$sku->id-") . '.png';
                                    $img->save($savePath);
                                }
                            }

                            //zip and download
                            $zip = new \ZipArchive();
                            $zipFileName = $folderName . '.zip';
                            if ($zip->open(public_path($folder . '/' . $zipFileName), \ZipArchive::CREATE) == TRUE) {
                                $files = File::files(public_path($folder));
                                foreach ($files as $key => $value) {
                                    $relativeName = basename($value);
                                    $zip->addFile($value, $relativeName);
                                }
                                $zip->close();
                            }

                            return response()->download(public_path($folder . '/' . $zipFileName))
                                ->deleteFileAfterSend();
                        }
                    ),
                ExportBulkAction::make()->exports([
                    ExcelExport::make()->withColumns([
                        Column::make('name'),
                        Column::make('product.description'),
                        Column::make('product.price'),
                        Column::make('quantity'),
                        Column::make('product.category.name'),

                    ]),
                ])
            ]);
    }

    public  static function imageXY($img, $data): array
    {
        $pad = 30;
        switch ($data['watermark_position']) {
            case 'top_left':
                $x = $y = $pad;
                break;
            case 'top_right':
                $x = $img->width() - ($pad + 20);
                $y = $pad;
                break;

            case 'bottom_left':
                $x = $pad;
                $y = $img->height() - $pad;
                break;

            case 'bottom_right':
                $x = $img->width() - ($pad + 20);
                $y = $img->height() - $pad;
                break;
            default:
                $x = $y = $pad;
                break;
        }

        return [$x, $y];
    }
}
