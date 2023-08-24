<?php

namespace App\Http\Integrations\SteadFast\Requests;

use App\Http\Integrations\SteadFast\SteadFastConnector;
use App\Models\Order;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Request\HasConnector;

class AddParcelRequest extends Request implements HasBody
{
    use HasJsonBody, HasConnector;

    protected string $connector = SteadFastConnector::class;

    protected Method $method = Method::POST;

    public function __construct(protected Order $order)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/create_order';
    }


    protected function defaultBody(): array
    {
        $order = $this->order;

        return [

            'invoice' => $order->id,
            'recipient_name' => $order->customer->name,
            'recipient_phone' => $order->customer->mobile,
            'recipient_address' => $order->customer->address,
            'cod_amount' => (int) $order->cod,
            'note' => $order->note_for_courier

        ];
    }
}
