<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum OrderItemStatus: string
{
    use EnumToArray;

    case WaitingForWholesalerApproval = 'waiting_for_wholesaler_approval';

    case Approved = 'approved';

    case DeliveredToHub = 'delivered_to_hub';

    case Cancelled = 'cancelled';
}
