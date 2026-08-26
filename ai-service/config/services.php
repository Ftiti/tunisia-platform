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

    // ── Ollama — LLM local ─────────────────────────────────────────────────────
    'ollama' => [
        'url'             => env('OLLAMA_URL',             'http://host.docker.internal:11434'),
        'model_chat'      => env('OLLAMA_MODEL_CHAT',      'mistral:7b'),
        'model_classify'  => env('OLLAMA_MODEL_CLASSIFY',  'mistral:7b'),
        'model_recommend' => env('OLLAMA_MODEL_RECOMMEND', 'llama3.1:8b'),
    ],

    // ── Provider Service ───────────────────────────────────────────────────────
    'provider' => [
        'url' => env('PROVIDER_SERVICE_URL', 'http://tunisia_provider:8002/api/v1'),
    ],

    // ── Booking Service ────────────────────────────────────────────────────────
    'booking' => [
        'url' => env('BOOKING_SERVICE_URL', 'http://tunisia_booking:8001/api'),
    ],

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

];
