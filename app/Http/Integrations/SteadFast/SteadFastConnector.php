<?php

namespace App\Http\Integrations\SteadFast;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class SteadFastConnector extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return (string) config('services.steadfast.base_url');
    }

    /**
     * Default headers for every request
     *
     * @return string[]
     */
    protected function defaultHeaders(): array
    {
        return [

            'Api-Key' => config('services.steadfast.key'),
            'Secret-Key' => config('services.steadfast.secret'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Default HTTP client options
     *
     * @return string[]
     */
    protected function defaultConfig(): array
    {
        return [];
    }
}
