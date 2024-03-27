<?php

namespace App\Enum;

use App\Traits\EnumToArray;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
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
            self::Cancelled => 'danger',
            self::Partial_Delivered => 'warning',
            default => 'warning'
        };
    }
    public function getLabel(): ?string
    {
        return match ($this) {
            self::WaitingForWholesalerApproval => 'Waiting for Manufacturer Approval',
            self::WaitingForHubCollection => 'Waiting for Hub Collection',
            self::ProcessingForHandOverToCourier => 'Hub Received',
            self::HandOveredToCourier => 'Handovered to Courier',
            default => $this->name
        };
    }

    public static function delivery_statuses(): array
    {
        return self::collection()->filter(fn ($name) => in_array($name, [
            'Delivered',
            'Partial_Delivered',
            'Cancelled'
        ]))->toArray();
    }
}
