<?php

namespace App\Filament\Resources\HubOrderResource\Pages;

use App\Enum\OrderItemStatus;
use App\Enum\OrderStatus;
use App\Enum\SystemRole;
use App\Filament\Resources\HubOrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

class ManageHubOrders extends ManageRecords
{
    protected static string $resource = HubOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function collect_product()
    {
        dd(func_get_args());
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return  OrderStatus::WaitingForHubCollection->name;
    }

    public function getTabs(): array
    {
        return OrderService::resource(static::$resource)::tabs(SystemRole::HubManager);
    }

    // protected function paginateTableQuery(Builder $query): Paginator
    // {
    //     return $query->simplePaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    // }
}
