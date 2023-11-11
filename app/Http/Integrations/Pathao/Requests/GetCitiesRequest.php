<?php

namespace App\Http\Integrations\Pathao\Requests;

use App\Http\Integrations\Pathao\PathaoConnector;
use App\Models\Order;
use Saloon\CachePlugin\Contracts\Cacheable;
use Saloon\CachePlugin\Contracts\Driver;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;
use Saloon\CachePlugin\Traits\HasCaching;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Request\HasConnector;
use Illuminate\Support\Facades\Cache;

class GetCitiesRequest extends Request implements Cacheable
{

    use HasConnector, HasCaching;

    protected string $connector = PathaoConnector::class;

    protected Method $method = Method::GET;

    public function resolveCacheDriver(): Driver
    {
        return new LaravelCacheDriver(Cache::store('file'));
    }

    public function cacheExpiryInSeconds(): int
    {
        return 3600 * 24;
    }

    public function resolveEndpoint(): string
    {
        return '/countries/1/city-list';
    }

    protected function defaultBody(): array
    {

        return [];
    }
}
