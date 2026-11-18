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

    // Industry list for the company forms (NAICS sectors from Oanor). Called from the server only;
    // the key never reaches the browser (App\Services\IndustryCatalog).
    'industry' => [
        'key' => env('INDUSTRY_API_KEY'),
        'url' => env('INDUSTRY_API_URL', 'https://api.oanor.com/naics-api/v1/naicsx/sectors-list'),
        // Every other level (subsectors down to national industries), 100 titles a call.
        'search_url' => env('INDUSTRY_API_SEARCH_URL', 'https://api.oanor.com/naics-api/v1/search'),
        // Pause before each call, in milliseconds: the free plan allows 2 calls a second.
        'pause_ms' => 600,
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

];
