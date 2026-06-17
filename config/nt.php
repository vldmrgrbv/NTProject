<?php

return [
    'api_url' => env('NT_API_URL', ''),
    'token' => env('NT_API_TOKEN', ''),

    'api2' => [
        'url' => env('NT_API2_URL', ''),
        'token' => env('NT_API2_TOKEN', ''),
    ],

    'fns_api' => [
        'base_url' => env('FNS_BASE_URL', ''),
        'user' => env('FNS_USER', ''),
    ],

    'limits' => [
        'receipts_daily' => 2,
        'receipts_monthly' => 4,
        'auth_code_minutes' => 5,
        'auth_code_attempts' => 5,
        'auth_code_attempts_reset_minutes' => 5,
    ],

    'product_api' => [
        'url' => env('NETWORK_URL', ''),
        'user' => env('NETWORK_USER', ''),
        'password' => env('NETWORK_PASSWORD', ''),
    ],

    'queue' => [
        'tries' => (int) env('NT_QUEUE_TRIES', 5),
        'backoff' => (int) env('NT_QUEUE_BACKOFF', 43200), // 12 hours
    ],

    'blacklist_inn' => [
        7721546864, // Wildberries
        9701048328, // Megamarket
    ],

    'max-bot' => [
        'url_mini_app' => env('MAXBOT_URL_MINI_APP', ''),
        'token' => env('MAXBOT_ACCESS_TOKEN', ''),
        'score_balance_limit' => 500,
        'api_secret' => env('MAXBOT_API_SECRET', ''),
    ],
];
