<?php

namespace App\Filament\Resources\ReturnItemsResource\Pages;

use App\Filament\Resources\ReturnItemsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ListReturnItems extends ListRecords
{
    protected static string $resource = ReturnItemsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Pending' => ListRecords\Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->where('is_returned_to_wholesaler', 0)
                        ->count()
                )
                ->query(
                    fn ($query) => $query->where('is_returned_to_wholesaler', 0)
                ),
            'Returned to Wholesaler' => ListRecords\Tab::make()
                ->badge(
                    self::$resource::getEloquentQuery()
                        ->where('is_returned_to_wholesaler', 1)
                        ->count()
                )
                ->query(
                    fn ($query) => $query->where('is_returned_to_wholesaler', 1)
                )
        ];
    }
}
