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

    'ai' => [
        'url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8001'),
    ],

    'othoba' => [
        // Othoba.com's own "search-only" Typesense key, embedded in their
        // page JS for their frontend search box to call directly - a
        // search-only key is designed to be public (read-only, rate
        // limited), not a secret. Kept configurable here in case they
        // rotate it. See app/StoreProviders/OthobaLiveProvider.php.
        'typesense_key' => env('OTHOBA_TYPESENSE_KEY', 'ekdHAQFqamb1XVMxKGLouPGTcwT9Wzgz'),
    ],

];
