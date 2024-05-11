<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class FraudCustomer extends Pivot
{
    use HasFactory;

    protected $table = 'fraud_customers';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
