<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLockAmount extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    //relations

    public function entity()
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->entity();
    }

    //helper functions

    public function details(): array
    {
        $type = $this->entity_type;
        $id = $this->entity_id;

        return match ($type) {
            Order::class => [
                'label' => 'Order',
                'url' => route('filament.app.resources.orders.index', ['tableSearch' => $id])
            ],
            FundWithdrawRequest::class =>
            [
                'label' => 'Fund withdraw request',
                'url' => route('filament.app.resources.fund-withdraw-requests.index', ['tableSearch' => $id])
            ],
            default => [
                'label' => (new \ReflectionClass($type))->getShortName(),
                'url' => $id
            ]
        };
    }
}
