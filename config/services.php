<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
    'pathao' => [
        'enabled' => env('PATHAO_ENABLED', false),
        'merchant_email' => env('PATHAO_MERCHANT_EMAIL'),
        'merchant_password' => env('PATHAO_MERCHANT_PASSWORD', ''),
        'base_url' => env('PATHAO_BASE_URL'),
        'client_id' => env('PATHAO_CLIENT_ID', ''),
        'client_secret' => env('PATHAO_CLIENT_SECRET'),
        'store_id' => env('PATHAO_STORE_ID')
    ],
    'steadfast' => [
        'enabled' => env('STEADFAST_ENABLED', false),
        'merchant_id' => env('STEADFAST_MERCHANT_ID', ''),
        'base_url' => env('STEADFAST_BASE_URL'),
        'key' => env('STEADFAST_API_KEY'),
        'secret' => env('STEADFAST_SECRET_KEY'),
    ],
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
