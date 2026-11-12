<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Encrypted Email Relay Master Switch & Domain
    |--------------------------------------------------------------------------
    */
    'enabled' => (bool) env('EMAIL_RELAY_ENABLED', true),

    'domain' => strtolower((string) env('EMAIL_RELAY_DOMAIN', 'localdatehub.com')),

    'provider' => strtolower((string) env('EMAIL_RELAY_PROVIDER', 'cloudflare')),

    'from_address' => env('EMAIL_RELAY_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@localdatehub.com')),

    'from_name' => env('EMAIL_RELAY_FROM_NAME', env('MAIL_FROM_NAME', 'LocalDateHub')),

    'queue' => env('EMAIL_RELAY_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Inbound & Status Webhook Verification Secrets
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'cloudflare' => [
            'secret' => env('EMAIL_RELAY_CLOUDFLARE_SECRET', ''),
        ],
        'mailgun' => [
            'signing_key' => env('EMAIL_RELAY_MAILGUN_SIGNING_KEY', env('MAILGUN_SECRET', '')),
        ],
        'postmark' => [
            'token' => env('EMAIL_RELAY_POSTMARK_TOKEN', ''),
        ],
        'sendgrid' => [
            'secret' => env('EMAIL_RELAY_SENDGRID_SECRET', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting & Attachment Security
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'per_ip_per_minute' => (int) env('EMAIL_RELAY_RATE_PER_MINUTE', 5),
        'per_ip_per_hour' => (int) env('EMAIL_RELAY_RATE_PER_HOUR', 30),
    ],

    'attachments' => [
        'max_count' => 3,
        'max_size_kb' => 5120, // 5 MB per file
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'txt'],
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
            'text/plain',
        ],
    ],
];

