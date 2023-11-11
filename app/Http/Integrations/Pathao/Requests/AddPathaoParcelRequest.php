<?php

namespace App\Http\Integrations\Pathao\Requests;

use App\Http\Integrations\Pathao\PathaoConnector;
use App\Models\Order;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Request\HasConnector;

class AddPathaoParcelRequest extends Request implements HasBody
{
    use HasConnector, HasJsonBody;

    protected string $connector = PathaoConnector::class;

    protected Method $method = Method::POST;

    public function __construct(public Order $order)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/orders';
    }

    protected function defaultBody(): array
    {

        $order = $this->order;
        $reseller = $order->reseller;
        $cta = 'প্রয়োজনে এই নাম্বারে কল করুন : ' . $reseller->mobile;

        $itemsCount = $order->items->count();
        $itemWeight = $itemsCount * 0.3;
        $description = $order->items->map(fn ($item) => $item->name . ' - ' . $item->quantity)
            ->implode(',');

        $data = [

            "store_id" => config('services.pathao.store_id'),
            "merchant_order_id" => $order->id,
            "sender_name" => $reseller->name,
            "sender_phone" => $reseller->mobile,
            "recipient_name" => $order->customer->name,
            "recipient_phone" => $order->customer->mobile,
            "recipient_address" => $order->customer->address,
            "recipient_city" => $order->customer->district_id,
            "recipient_zone" => $order->customer->upazila_id,
            "delivery_type" => "48",
            "item_type" => "2",
            "special_instruction" => $order->note_for_courier . ' ' . $cta,
            "item_quantity" => $itemsCount,
            "item_weight" => $itemWeight,
            "amount_to_collect" => (int) $order->cod,
            "item_description" => $description

        ];

        if ($area = $order->customer->area_id)
            $data["recipient_area"] = $area;

        return $data;
    }
}
