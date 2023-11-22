<?php

namespace App\Models;

use App\Enum\PaymentChannel as EnumPaymentChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentChannel extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'type' => EnumPaymentChannel::class,
    ];

    //scopes

    public function scopeType(Builder $builder, $type): void
    {
        $builder->whereType($type);
    }

    public function scopeMine(Builder $builder): void
    {
        $builder->whereBelongsTo(auth()->user());
    }

    //relations

    /**
     * Get the user that owns the PaymentChannel
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    //helpers

    public static function list($type): array
    {
        $list = self::query()
            ->type($type)
            ->mine()
            ->get()
            ->map(function ($item) use ($type) {
                return [
                    'id' => $item->id,
                    'label' => $type == EnumPaymentChannel::Bank->value ? $item->account_name : $item->mobile_no,
                ];
            })
            ->pluck('label', 'id')
            ->toArray();

        //logger($list);

        return $list;
    }
}
