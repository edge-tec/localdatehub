<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Asynchronous Visitor Telemetry
    |--------------------------------------------------------------------------
    |
    | When enabled and the queue connection is non-sync (e.g., Redis),
    | public GET page visits are dispatched asynchronously to the analytics
    | queue instead of executing synchronous MySQL writes during HTTP requests.
    |
    */
    'async_enabled' => env('ANALYTICS_ASYNC_ENABLED', true),

    'queue' => env('ANALYTICS_QUEUE', 'analytics'),

    'dedup_ttl_seconds' => (int) env('ANALYTICS_DEDUP_TTL', 1800),
];

