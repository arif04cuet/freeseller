<?php

namespace App\Filament\Resources\ResellersResource\Pages;

use App\Filament\Resources\ResellersResource;
use App\Traits\RecordCountTrait;
use App\Traits\UseSimplePagination;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListResellers extends ListRecords
{
    use UseSimplePagination;
    use RecordCountTrait;

    protected static string $resource = ResellersResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['business', 'wallet'])
            ->resellers()
            ->latest();
    }
}
