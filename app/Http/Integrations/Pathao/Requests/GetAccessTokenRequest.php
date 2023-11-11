<?php

namespace App\Http\Integrations\Pathao\Requests;

use App\Http\Integrations\Pathao\PathaoConnector;
use App\Models\Order;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Request\HasConnector;

class GetAccessTokenRequest extends Request implements HasBody
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

        return [

            "client_id" => config('services.pathao.client_id'),
            "client_secret" => config('services.pathao.client_secret'),
            "username" => config('services.pathao.merchant_email'),
            "password" => config('services.pathao.merchant_password'),
            "grant_type" => "password"

        ];
    }
}
