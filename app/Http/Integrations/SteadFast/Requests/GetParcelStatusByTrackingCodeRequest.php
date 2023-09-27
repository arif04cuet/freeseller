<?php

namespace App\Http\Integrations\SteadFast\Requests;

use App\Http\Integrations\SteadFast\SteadFastConnector;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Request\HasConnector;

class GetParcelStatusByTrackingCodeRequest extends Request
{
    use HasConnector;

    protected string $connector = SteadFastConnector::class;

    protected Method $method = Method::GET;

    public function __construct(protected string $tracking_code)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/status_by_trackingcode/'.$this->tracking_code;
    }
}
