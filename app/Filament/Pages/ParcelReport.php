<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ParcelReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.parcel-report';

    public $from;
    public $to;

    public function getReport()
    {
        $report = null;

        $from = $this->from;
        $to = $this->to;
        if ($from && $to) {
        }

        return $report;
    }
    protected function getViewData(): array
    {
        return [
            'name' => 'Arif',
            'from' => $this->from,
            'to' => $this->to,
        ];
    }
}
