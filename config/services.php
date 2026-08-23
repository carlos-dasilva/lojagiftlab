<?php

return [

    'melhor_envio' => [
        'token' => env('MELHOR_ENVIO_TOKEN'),
        'from_postal_code' => env('MELHOR_ENVIO_FROM_POSTAL_CODE', '92010000'),
        'base_url' => env('MELHOR_ENVIO_BASE_URL', 'https://melhorenvio.com.br/api/v2'),
        'user_agent' => env('MELHOR_ENVIO_USER_AGENT', 'Gift Lab (lojagiftlab@gmail.com)'),
    ],

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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
