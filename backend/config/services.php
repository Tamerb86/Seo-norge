<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Supabase, OpenAI, Stripe, and various scraping providers.
    |
    */

    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'key' => env('SUPABASE_KEY'),
        'jwt_secret' => env('SUPABASE_JWT_SECRET'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],

    'scraping' => [
        'provider' => env('SCRAPING_PROVIDER', 'scrapingrobot'),
        'api_key' => env('SCRAPING_API_KEY'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'google' => [
        'ads' => [
            'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
            'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        ],
        'search_console' => [
            'client_id' => env('GOOGLE_SEARCH_CONSOLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_SEARCH_CONSOLE_CLIENT_SECRET'),
        ],
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

];
