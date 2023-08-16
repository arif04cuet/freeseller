<?php

namespace App\Enum;

use App\Traits\EnumToArray;

enum WalletRechargeRequestStatus: string
{
    use EnumToArray;

    case Pending = 'pending';

    case Rejected = 'rejected';

    case Approved = 'approved';
}
