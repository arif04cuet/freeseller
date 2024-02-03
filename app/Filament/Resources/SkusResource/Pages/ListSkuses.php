<?php

namespace App\Filament\Resources\SkusResource\Pages;

use App\Filament\Resources\SkusResource;
use App\Models\Sku;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListSkuses extends ListRecords
{
    protected static string $resource = SkusResource::class;

    public $subH = null;
    public function getSubheading(): string | Htmlable | null
    {
        if (!$this->subH)
            $this->subH = 'Total = ' . static::getResource()::getEloquentQuery()->sum('quantity');

        return $this->subH;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
