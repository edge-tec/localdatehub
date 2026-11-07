<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Identity & Branding
    |--------------------------------------------------------------------------
    */
    'name' => env('APP_NAME', 'LocalDateHub'),
    'tagline' => 'Connecting Local Adults Safely and Privately',
    'support_email' => env('SUPPORT_EMAIL', 'support@localdatehub.com'),
    'contact_email' => env('CONTACT_EMAIL', 'contact@localdatehub.com'),

    /*
    |--------------------------------------------------------------------------
    | Product Safety & Age Assurance
    |--------------------------------------------------------------------------
    | LocalDateHub is an adult personal classifieds and dating connections
    | platform. It strictly enforces 18+ membership and prohibits non-consensual
    | content, exploitation, and illegal sexual commerce.
    */
    'safety' => [
        'min_age' => (int) env('LOCALDATEHUB_AGE_MINIMUM', 18),
        'require_age_confirmation' => true,
        'require_terms_acceptance' => true,
        'prohibited_commercial_solicitation' => true,
        'moderation_auto_flag' => (bool) env('LOCALDATEHUB_MODERATION_AUTO_FLAG', true),
        'photo_exif_strip' => (bool) env('LOCALDATEHUB_PHOTO_EXIF_STRIP', true),
        'max_photos_per_user' => (int) env('LOCALDATEHUB_MAX_PHOTOS', 6),
        'max_photo_size_kb' => (int) env('LOCALDATEHUB_MAX_PHOTO_SIZE_KB', 8192), // 8MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Geolocation & Privacy Safeguards
    |--------------------------------------------------------------------------
    | Exact GPS coordinates are never exposed to clients. Distance bucketing
    | and location jitter obfuscation protect user physical safety.
    */
    'geolocation' => [
        'fuzzing_enabled' => (bool) env('LOCALDATEHUB_LOCATION_FUZZING_ENABLED', true),
        'fuzzing_jitter_meters' => [
            'min' => 300,
            'max' => 800,
        ],
        'default_radius_km' => (int) env('LOCALDATEHUB_DEFAULT_RADIUS_KM', 25),
        'max_radius_km' => (int) env('LOCALDATEHUB_MAX_RADIUS_KM', 150),
        'distance_brackets' => [
            'close' => 1,     // "Less than a km away"
            'moderate' => 10, // whole numbers
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting & Abuse Throttles (per minute)
    |--------------------------------------------------------------------------
    */
    'throttling' => [
        'swipes_per_minute' => (int) env('THROTTLE_SWIPES_PER_MIN', 60),
        'messages_per_minute' => (int) env('THROTTLE_MESSAGES_PER_MIN', 20),
        'searches_per_minute' => (int) env('THROTTLE_SEARCHES_PER_MIN', 30),
        'contact_messages_per_minute' => (int) env('THROTTLE_CONTACT_PER_MIN', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Access
    |--------------------------------------------------------------------------
    */
    'health_check' => [
        'secret_key' => env('LOCALDATEHUB_HEALTH_CHECK_SECRET', 'local-development-health-key'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        'billing_enabled' => (bool) env('FEATURE_BILLING_ENABLED', false),
        'sms_verification_enabled' => (bool) env('FEATURE_SMS_VERIFICATION_ENABLED', false),
        'date_spots_enabled' => (bool) env('FEATURE_DATE_SPOTS_ENABLED', true),
        'curated_meetups_enabled' => (bool) env('FEATURE_CURATED_MEETUPS_ENABLED', true),
    ],
];
