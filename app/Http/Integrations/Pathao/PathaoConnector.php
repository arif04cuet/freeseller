<?php

namespace App\Http\Integrations\Pathao;

use App\Http\Integrations\Pathao\Requests\GetAccessTokenByRefreshTokenRequest;
use App\Http\Integrations\Pathao\Requests\GetAccessTokenRequest;
use Saloon\Http\Connector;
use Saloon\Contracts\PendingRequest;
use Saloon\Http\OAuth2\GetRefreshTokenRequest;
use Saloon\Traits\Plugins\AcceptsJson;

class PathaoConnector extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL of the API
     *
     * @return string
     */
    public function resolveBaseUrl(): string
    {
        return (string) config('services.pathao.base_url');
    }

    /**
     * Default headers for every request
     *
     * @return string[]
     */
    protected function defaultHeaders(): array
    {
        return [
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

    public function boot(PendingRequest $pendingRequest): void
    {

        if (
            $pendingRequest->getRequest() instanceof GetAccessTokenRequest ||
            $pendingRequest->getRequest() instanceof GetAccessTokenByRefreshTokenRequest

        ) {
            return;
        }


        // Now let's make our authentication request. Since we are in the
        // context of the connector, we can just simply call $this and
        // make another request!

        $accessToken = cache('pathao_access_token');

        // Now we'll take the token from the auth response and then pass it
        // into the $pendingRequest which is the original GetSongsByArtistRequest.

        $pendingRequest->withTokenAuth($accessToken);
    }
}
