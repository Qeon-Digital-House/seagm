<?php

return [
    'account_id' => env('SEAGM_ACCOUNT_ID'),
    'secret_key' => env('SEAGM_SECRET_KEY'),

    'is_production' => env('SEAGM_IS_PRODUCTION', true),

    'base_url' => env(
        'SEAGM_BASE_URL',
        env('SEAGM_IS_PRODUCTION', true)
            ? 'https://openapi.seagm.com'
            : 'https://openapi.seagm.io'
    ),

    'timeout'        => env('SEAGM_TIMEOUT', 30),
    'callback_url'   => env('SEAGM_CALLBACK_URL'),
    'callback_token' => env('SEAGM_CALLBACK_TOKEN'),
];
