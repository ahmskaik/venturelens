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

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'timeout' => env('GEMINI_TIMEOUT', 60),
        'max_retries' => env('GEMINI_MAX_RETRIES', 5),
        'chat_timeout' => env('GEMINI_CHAT_TIMEOUT', 30),
        'chat_max_retries' => env('GEMINI_CHAT_MAX_RETRIES', 2),
        'models' => [
            'flash' => env('GEMINI_MODEL_FLASH', 'gemini-2.5-flash'),
            'pro' => env('GEMINI_MODEL_PRO', 'gemini-2.5-pro'),
        ],
    ],

];
