<?php

namespace App\Filament\Resources\ListResource\Pages;

use App\Filament\Resources\ListResource;
use App\Traits\UseSimplePagination;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

class ListLists extends ListRecords
{

    protected static string $resource = ListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
