<?php

use App\Enum\Courier;

return [
    'base_id_number' => 1000,
    'steadfast_cod_percentange' => 1,
    'minimum_acount_balance' => 1000,
    'platform_fee' => 2, //%
    'cod_fee' => 1, //%
    'packaging_fee' => 10, //taka
    'delivery_charge' => 120,
    'delivery_charge_same_city' => 70,
    'per_saree_weight' => 500, //g,
    'support_number' => '01766-652553',
    'platform_bkash' => '01717348147',
    'low_stock_threshold' => 5,
    'default_courier' => Courier::SteadFast->value,
    'add_parcel_manually' => 0,
    'fund_transfer_fee' => 0,
];
