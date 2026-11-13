<?php

use Illuminate\Support\Str;

return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'localdatehub'), '_') . '_horizon:'),
    'middleware' => ['web', 'auth', 'admin.rbac'],

    'waits' => [
        'redis:default' => 60,
        'redis:analytics' => 120,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,
    'memory_limit' => 256,

    'defaults' => [
        'supervisor-core' => [
            'connection' => 'redis',
            'queue' => ['critical', 'sweeping', 'verification', 'monitoring', 'default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 6,
            'maxTime' => 3600,
            'maxJobs' => 500,
            'memory' => 256,
            'tries' => 3,
            'backoff' => [5, 30, 60],
            'timeout' => 90,
            'nice' => 0,
        ],
        'supervisor-analytics' => [
            'connection' => 'redis',
            'queue' => ['analytics'],
            'balance' => 'auto',
            'maxProcesses' => 8,
            'maxTime' => 3600,
            'maxJobs' => 2000,
            'memory' => 256,
            'tries' => 3,
            'backoff' => [5, 30, 60],
            'timeout' => 30,
            'nice' => 5,
        ],
        'supervisor-comms' => [
            'connection' => 'redis',
            'queue' => ['notifications', 'emails', 'advertising', 'maintenance'],
            'balance' => 'auto',
            'maxProcesses' => 4,
            'maxTime' => 3600,
            'maxJobs' => 1000,
            'memory' => 256,
            'tries' => 3,
            'backoff' => [10, 60, 180],
            'timeout' => 60,
            'nice' => 5,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-core' => ['minProcesses' => 2, 'maxProcesses' => 6],
            'supervisor-analytics' => ['minProcesses' => 2, 'maxProcesses' => 8],
            'supervisor-comms' => ['minProcesses' => 1, 'maxProcesses' => 4],
        ],
        'staging' => [
            'supervisor-core' => ['minProcesses' => 1, 'maxProcesses' => 3],
            'supervisor-analytics' => ['minProcesses' => 1, 'maxProcesses' => 4],
            'supervisor-comms' => ['minProcesses' => 1, 'maxProcesses' => 2],
        ],
        'local' => [
            'supervisor-core' => ['maxProcesses' => 2],
            'supervisor-analytics' => ['maxProcesses' => 2],
            'supervisor-comms' => ['maxProcesses' => 1],
        ],
    ],
];

