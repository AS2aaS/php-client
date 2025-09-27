<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AS2aaS API Key
    |--------------------------------------------------------------------------
    |
    | Your AS2aaS API key. You can find this in your AS2aaS dashboard.
    | Use pk_live_* for production and pk_test_* for testing.
    |
    */
    'api_key' => env('AS2AAS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum time in milliseconds to wait for API requests to complete.
    |
    */
    'timeout' => env('AS2AAS_TIMEOUT', 30000),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Number of retries for failed requests and delay between retries.
    |
    */
    'retries' => env('AS2AAS_RETRIES', 3),
    'retry_delay' => env('AS2AAS_RETRY_DELAY', 1000),

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for AS2 messages and partners.
    |
    */
    'default_mdn_mode' => env('AS2AAS_DEFAULT_MDN_MODE', 'async'),
    'default_signing' => env('AS2AAS_DEFAULT_SIGNING', true),
    'default_encryption' => env('AS2AAS_DEFAULT_ENCRYPTION', true),
    'auto_validate_edi' => env('AS2AAS_AUTO_VALIDATE_EDI', false),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for handling AS2aaS webhooks in your Laravel application.
    |
    */
    'webhooks' => [
        'secret' => env('AS2AAS_WEBHOOK_SECRET'),
        'tolerance' => env('AS2AAS_WEBHOOK_TOLERANCE', 300), // 5 minutes
    ],
];
