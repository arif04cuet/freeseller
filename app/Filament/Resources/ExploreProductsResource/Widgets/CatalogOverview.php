<?php

namespace App\Filament\Resources\ExploreProductsResource\Widgets;

use App\Models\Category;
use App\Models\Sku;
use Cache;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CatalogOverview extends BaseWidget
{
    protected static bool $isLazy = false;
    protected static ?string $pollingInterval = null;
    protected static string $view = 'filament.widgets.explore-products-stats-overview-widget';

    protected function getStats(): array
    {
        $productCount = Cache::remember('products_count', 60 * 60, fn () => Sku::query()->count());
        $stockCount = Cache::remember('stock_amount', 60 * 60, fn () =>  Sku::query()->sum('quantity'));
        $categories = Cache::remember('categories_count', 60 * 60 * 24, fn () =>  Category::query()->count());

        return [
            Stat::make('Products', $productCount)->extraAttributes(['class' => 'p-4']),
            Stat::make('Stock', $stockCount),
            Stat::make('Catagories', $categories),
        ];
    }
}
