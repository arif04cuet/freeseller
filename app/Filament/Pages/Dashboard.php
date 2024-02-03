<?php

namespace App\Filament\Pages;

use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getTitle(): string | Htmlable
    {
        return '';
    }

    public function getColumns(): int | string | array
    {
        return 4;
    }
}
