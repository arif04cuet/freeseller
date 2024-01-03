<?php

namespace App\Enum;

use App\Traits\EnumToArray;
use Filament\Support\Contracts\HasLabel;

enum TransactionMetaText: string
{
    use EnumToArray;

    case ORDER_AMOUNT_DIPOSITED = 'order_amount_diposited';
    case PRODUCT_VALUE_DIPOSITED = 'product_value_diposited';
    case COD_VALUE_DIPOSITED = 'cod_value_diposited';

    case PLATFORM_FEE_DEDUCTED = 'platform_fee_deducted';
    case COURIER_FEE_DEDUCTED = 'courier_fee_deducted';
    case PACKAGING_FEE_DEDUCTED = 'packaging_fee_deducted';
    case COD_FEE_DEDUCTED = 'cod_fee_deducted';
    case PRODUCT_COST_DEDUCTED = 'product_cost_deducted';
    case ORDER_CLAIM = 'order_claim';

    public function getLabel($order): ?string
    {

        return match ($this) {
            self::ORDER_AMOUNT_DIPOSITED => 'Order amount diposited for order#' . $order->id,
            self::PRODUCT_VALUE_DIPOSITED => 'Product value diposited for order#' . $order->id,
            self::COD_VALUE_DIPOSITED => 'Order COD amount diposited for order#' . $order->id,
            self::PLATFORM_FEE_DEDUCTED => 'Platform fee deducted for order#' . $order->id,
            self::COURIER_FEE_DEDUCTED => 'Courier fee deducted for order#' . $order->id,
            self::PACKAGING_FEE_DEDUCTED => 'Packaging fee deducted for order#' . $order->id,
            self::COD_FEE_DEDUCTED => 'COD fee deducted for order#' . $order->id,
            self::PRODUCT_COST_DEDUCTED => 'Product cost deducted for order#' . $order->id,
            self::ORDER_CLAIM => 'Order claim amount from wholesaler to reseller for order#' . $order->id,
        };
    }
}
