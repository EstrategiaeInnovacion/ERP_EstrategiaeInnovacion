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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'groq' => [
        'key'   => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],

    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
        'logistica_webhook_url' => env('N8N_LOGISTICA_WEBHOOK_URL'),
    ],

    'microsoft_graph' => [
        // Prefiere MICROSOFT_GRAPH_* (usadas por RH, funcionales) y cae a MS_GRAPH_* como respaldo
        'client_id'     => env('MICROSOFT_GRAPH_CLIENT_ID',     env('MS_GRAPH_CLIENT_ID')),
        'tenant_id'     => env('MICROSOFT_GRAPH_TENANT_ID',     env('MS_GRAPH_TENANT_ID')),
        'client_secret' => env('MICROSOFT_GRAPH_CLIENT_SECRET', env('MS_GRAPH_CLIENT_SECRET')),
        'sender_email'  => env('MS_GRAPH_SENDER_EMAIL', env('MAIL_FROM_ADDRESS', 'sistemas@estrategiaeinnovacion.com.mx')),
    ],

];
