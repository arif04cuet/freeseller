<?php

namespace App\Filament\Resources\SkusResource\Pages;

use App\Filament\Resources\SkusResource;
use App\Models\Sku;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSkuses extends ListRecords
{
    protected static string $resource = SkusResource::class;

    public $skus = [];

    public $updatedSkus = [];

    public function updated()
    {

        foreach ($this->skus as $sku => $value) {

            if (!isset($this->updatedSkus[$sku])) {

                Sku::find($sku)->update([
                    'quantity' => (int)$value['qnt']
                ]);
                $value['status'] = true;

                Notification::make()
                    ->title('Quantity Updated')
                    ->success()
                    ->send();
            }


            $this->updatedSkus[$sku] = $value;
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
