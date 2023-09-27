<?php

namespace App\Filament\Resources\ResellersResource\Pages;

use App\Filament\Resources\ResellersResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListResellers extends ListRecords
{
    protected static string $resource = ResellersResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with('business')->resellers()->latest();
    }
}
