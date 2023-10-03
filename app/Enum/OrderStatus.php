<?php

namespace App\Enum;

use App\Traits\EnumToArray;
use Filament\Support\Contracts\HasColor;

enum OrderStatus: string implements HasColor
{
    use EnumToArray;

    case WaitingForWholesalerApproval = 'waiting_for_wholesaler_approval';

    case Processing = 'processing';

    case WaitingForHubCollection = 'waiting_for_hub_collection';

    case ProcessingForHandOverToCourier = 'processing_for_handover_to_courier';

    case HandOveredToCourier = 'hand_overed_to_courier';

    case Cancelled = 'cacelled';

    case Approved = 'approved';

    case Delivered = 'delivered';
    case Partial_Delivered = 'partial_delivered';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::WaitingForWholesalerApproval => 'secondary',
            self::WaitingForHubCollection => 'secondary',
            self::ProcessingForHandOverToCourier => 'secondary',
            self::HandOveredToCourier => 'secondary',
            self::Processing => 'warning',
            self::Delivered => 'success',
            self::Cancelled => 'danger'
        };
    }
}
