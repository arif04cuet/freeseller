<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum OrderStatus: string
{
    use EnumToArray;

    case WaitingForWholesalerApproval = 'waiting_for_wholesaler_approval';

    case Processing = 'processing';

    case WaitingForHubCollection = 'waiting_for_hub_collection';

    case ProcessingForHandOverToCourier = 'processing_for_handover_to_courier';

    case HandOveredToCourier = 'hand_overed_to_courier';

    case Courier_In_Review = 'in_review';

    case Cancelled = 'cacelled';

    case Approved = 'approved';

    case Delivered = 'delivered';
}
