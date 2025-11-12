<?php

use Illuminate\Support\Str;

return [

    'name' => env('HORIZON_NAME', 'WhatsMark-SaaS Queue Monitor'),

    'domain' => env('HORIZON_DOMAIN'),

    'path' => env('HORIZON_PATH', 'admin/horizon'),

    /*
            |--------------------------------------------------------------------------
            | Horizon Redis Connection (isolated from cache + queue)
            |--------------------------------------------------------------------------
    */
    'use' => 'horizon',

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    'middleware' => ['web', 'auth', 'admin'],

    /*
            |--------------------------------------------------------------------------
            | Queue Wait Time Alerts (seconds)
            |--------------------------------------------------------------------------
            |
            | WhatsApp queue: 10s (campaigns can queue briefly during bursts)
            | Default queue: 60s (background tasks can tolerate delays)
    */
    'waits' => [
        'redis:whatsapp-messages' => 10,
        'redis:default' => 60,
    ],

    /*
            |--------------------------------------------------------------------------
            | Job History Retention (minutes)
            |--------------------------------------------------------------------------
            |
            | Completed jobs kept for 24 hours (1440 min) for campaign debugging
            | Failed jobs kept for 1 week for troubleshooting
    */
    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 1440,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [],
    'silenced_tags' => ['system-internal'],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 320,

    /*
            |--------------------------------------------------------------------------
            | Supervisor Definitions
            |--------------------------------------------------------------------------
    */
    'defaults' => [

        /*
                    |----------------------------------------------------------------------
                    | HIGH PRIORITY - WHATSAPP QUEUE (Campaign & Message Heavy Load)
                    |----------------------------------------------------------------------
                    | - Handles ALL campaigns and WhatsApp API operations
                    | - Gets 75% of worker resources (2-5 processes)
                    | - Scales aggressively under campaign load
                    | - Optimized for 4 CPU / 8GB RAM
                    | - Higher memory allocation for API-heavy operations
        */
        'supervisor-whatsapp' => [
            'connection' => 'redis',
            'queue' => ['whatsapp-messages'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 2,
            'maxProcesses' => 5, // Optimized for 4 CPU (1.25 workers per CPU max)
            'maxTime' => 1800, // 30 min - faster recycling for campaign bursts
            'maxJobs' => 250, // Restart after 250 jobs (campaign memory safety)
            'memory' => 320, // Increased for API calls (was 256)
            'tries' => 2, // Retry once (prevents duplicate sends)
            'timeout' => 300, // 5 min for slow WhatsApp APIs
            'nice' => 0, // HIGHEST CPU priority
            'balanceMaxShift' => 2, // Aggressive scaling (scale by 2 workers)
            'balanceCooldown' => 3, // Quick response to campaign load spikes
        ],

        /*
                    |----------------------------------------------------------------------
                    | LOW PRIORITY - DEFAULT QUEUE (Background Tasks)
                    |----------------------------------------------------------------------
                    | - Emails, notifications, cleanup, system background tasks
                    | - Gets 25% of worker resources (1-2 processes)
                    | - Runs in background, yields CPU to WhatsApp queue
                    | - Can tolerate delays and slower processing
        */
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 1,
            'maxProcesses' => 2, // Limited for background tasks
            'maxTime' => 3600, // 1 hour (can run longer, not critical)
            'maxJobs' => 500, // More jobs per worker (less cycling overhead)
            'memory' => 192, // Modest memory for background tasks
            'tries' => 3, // More retries OK (not time-critical)
            'timeout' => 180, // 3 min timeout (background can wait)
            'nice' => 10, // LOWEST CPU priority (yields to WhatsApp)
            'balanceMaxShift' => 1, // Slow scaling (not urgent)
            'balanceCooldown' => 10, // Wait longer before scaling (background only)
        ],
    ],

    /*
            |--------------------------------------------------------------------------
            | Per-Environment Overrides
            |--------------------------------------------------------------------------
    */
    'environments' => [
        'production' => [
            'supervisor-whatsapp' => [
                'minProcesses' => 2, // Always keep 2 workers ready
                'maxProcesses' => 5, // Optimized for 4 CPU / 8GB RAM
            ],
            'supervisor-default' => [
                'minProcesses' => 1, // Keep 1 background worker
                'maxProcesses' => 2, // Max 2 for background tasks
            ],
        ],

        'local' => [
            'supervisor-whatsapp' => [
                'minProcesses' => 1, // Minimal for testing
                'maxProcesses' => 2, // Limited for local development
            ],
            'supervisor-default' => [
                'minProcesses' => 1,
                'maxProcesses' => 1, // Single worker for local
            ],
        ],
    ],
];
