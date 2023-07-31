<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum OrderStatus: string
{
    use EnumToArray;

    case WaitingForWholesalerApproval = 'waiting_for_wholesaler_approval';

    case Processing = 'processing';

    case WaitingForHubCollection = 'waiting_for_hub_collection';

    case HandOveredToCourier = 'hand_overed_to_courier';

    case Cancelled = 'cacelled';

    case Approved = 'approved';
}
