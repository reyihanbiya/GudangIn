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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'tesseract' => [
        'binary' => env('TESSERACT_BINARY', 'tesseract'),
        'languages' => env('TESSERACT_LANGUAGES', 'eng+ind'),
        'timeout' => env('TESSERACT_TIMEOUT', 60),
    ],
    'xendit' => [
        'simulation' => env('XENDIT_SIMULATION', true),
        'secret_key' => env('XENDIT_SECRET_KEY'),
        'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),
        'api_url' => env('XENDIT_API_URL', 'https://api.xendit.co'),
    ],
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    ],
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
        'store_latitude' => env('STORE_LATITUDE'),
        'store_longitude' => env('STORE_LONGITUDE'),
    ],
    'customer_store' => [
        'delivery_fee' => env('STORE_DELIVERY_FEE', 0),
        'bank_name' => env('STORE_BANK_NAME'),
        'bank_account' => env('STORE_BANK_ACCOUNT'),
        'bank_holder' => env('STORE_BANK_HOLDER'),
    ],

];
