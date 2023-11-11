<?php

namespace App\Http\Integrations\Pathao\Requests;

use App\Http\Integrations\Pathao\PathaoConnector;
use App\Models\Order;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Request\HasConnector;

class GetAccessTokenByRefreshTokenRequest extends Request implements HasBody
{
    use HasConnector, HasJsonBody;

    protected string $connector = PathaoConnector::class;

    protected Method $method = Method::POST;


    public function resolveEndpoint(): string
    {
        return '/issue-token';
    }

    protected function defaultBody(): array
    {

        $refreshToken = cache('pathao_refresh_token');

        return [

            "client_id" => config('services.pathao.client_id'),
            "client_secret" => config('services.pathao.client_secret'),
            "refresh_token" => $refreshToken,
            "grant_type" => "refresh_token"

        ];
    }
}
