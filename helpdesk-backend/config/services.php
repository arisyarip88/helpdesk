<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'b2b_primary' => [
    'url'     => env('B2B_PRIMARY_URL', 'https://devhrms.sasmitagroup.org/api/v2/login-penugasan'),
    'api_key' => env('B2B_PRIMARY_API_KEY', '3ScAWtoIWsm5BjE7gtrkcGlIlQWX5zU2J9KjgQzb6v8rv2JEr7P4kHDHbtfzdSFVjR3Bia6y66afMjxFEI16Iiq8dT8PfzEG'),


    ],

];
