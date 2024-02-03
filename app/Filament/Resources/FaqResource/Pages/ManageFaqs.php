<?php

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use App\Traits\RecordCountTrait;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFaqs extends ManageRecords
{
    use RecordCountTrait;

    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
