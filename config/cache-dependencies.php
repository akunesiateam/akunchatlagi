<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin to Tenant Cache Dependencies
    |--------------------------------------------------------------------------
    |
    | Maps admin cache tags to their dependent tenant cache tags.
    | When an admin tag is invalidated, all mapped tenant tags will be
    | automatically invalidated across all active tenants.
    |
    | Format: 'admin_tag' => ['tenant_tag1', 'tenant_tag2', ...]
    |
    | Example: When 'plans' is invalidated in admin cache, it cascades to
    | invalidate 'subscriptions', 'features', 'limits', and 'billing' in
    | all tenant caches.
    |
    */
    'admin_to_tenant' => [
        // Plan changes affect tenant subscriptions and features
        'plans' => [
            'subscriptions',
            'features',
            'limits',
            'billing',
            'plan_features',
        ],

        // Feature changes affect tenant access control
        'features' => [
            'feature_access',
            'limits',
            'permissions',
            'plan_features',
        ],

        // Currency changes affect tenant billing
        'currencies' => [
            'billing',
            'invoices',
            'payments',
            'subscriptions',
        ],

        // Tax changes affect tenant invoicing
        'taxes' => [
            'billing',
            'invoices',
            'payments',
        ],

        // Language changes affect tenant UI and translations
        'languages' => [
            'translations',
            'settings',
            'ui',
            'locale',
        ],

        // Translation changes affect tenant content
        'translations' => [
            'ui',
            'locale',
            'content',
        ],

        // Email template changes affect tenant notifications
        'email_templates' => [
            'notifications',
            'emails',
        ],

        // System settings may affect tenant configuration
        'system_settings' => [
            'settings',
            'configuration',
        ],

        // Gateway settings affect tenant payment processing
        'payment_gateways' => [
            'billing',
            'payments',
            'subscriptions',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant to Admin Cache Dependencies
    |--------------------------------------------------------------------------
    |
    | Maps tenant cache tags to their dependent admin cache tags.
    | When a tenant tag is invalidated, specified admin tags will be
    | automatically invalidated to refresh aggregated statistics.
    |
    | Format: 'tenant_tag' => ['admin_tag1', 'admin_tag2', ...]
    |
    | Example: When 'contacts' is invalidated in tenant cache, it cascades to
    | invalidate 'tenant_stats', 'dashboard', and 'reports' in admin cache.
    |
    */
    'tenant_to_admin' => [
        // Contact changes affect admin statistics
        'contacts' => [
            'tenant_stats',
            'dashboard',
            'reports',
            'analytics',
        ],

        // Subscription changes affect admin revenue tracking
        'subscriptions' => [
            'revenue_stats',
            'dashboard',
            'billing_reports',
            'analytics',
            'tenant_stats',
        ],

        // Campaign changes affect admin usage statistics
        'campaigns' => [
            'tenant_stats',
            'dashboard',
            'usage_stats',
            'analytics',
        ],

        // Message changes affect admin usage and limits
        'messages' => [
            'usage_stats',
            'tenant_stats',
            'dashboard',
            'analytics',
        ],

        // Template changes may affect admin template statistics
        'templates' => [
            'tenant_stats',
            'analytics',
        ],

        // Group changes affect admin organization statistics
        'groups' => [
            'tenant_stats',
            'analytics',
        ],

        // Payment changes affect admin financial reports
        'payments' => [
            'revenue_stats',
            'billing_reports',
            'dashboard',
            'analytics',
        ],

        // Invoice changes affect admin financial tracking
        'invoices' => [
            'billing_reports',
            'revenue_stats',
            'analytics',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bidirectional Cache Tags
    |--------------------------------------------------------------------------
    |
    | Tags that require invalidation in BOTH admin and tenant layers
    | simultaneously. Changes to these tags affect both layers equally.
    |
    | Format: 'tag_name' => true
    |
    | Example: Language changes must be reflected in both admin panel
    | and all tenant interfaces immediately.
    |
    */
    'bidirectional' => [
        // Languages affect both admin and tenant interfaces
        'languages' => true,

        // Translations affect both admin and tenant content
        'translations' => true,

        // System announcements visible to both admin and tenants
        'announcements' => true,

        // Application settings may affect both layers
        'app_settings' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Critical Cache Tags
    |--------------------------------------------------------------------------
    |
    | Tags that should ALWAYS use queued invalidation when affecting
    | multiple tenants to prevent blocking requests.
    |
    | Format: ['tag1', 'tag2', ...]
    |
    */
    'critical_tags' => [
        'plans',
        'features',
        'currencies',
        'languages',
        'translations',
        'payment_gateways',
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm-on-Invalidate Tags
    |--------------------------------------------------------------------------
    |
    | Tags that should be automatically re-warmed after invalidation
    | to prevent cache stampede and maintain performance.
    |
    | Format: 'tag_name' => true
    |
    */
    'warm_on_invalidate' => [
        'plans' => true,
        'features' => true,
        'currencies' => true,
        'languages' => true,
        'translations' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for queued cache invalidation jobs.
    |
    */
    'queue' => [
        // Queue name for cache invalidation jobs
        'name' => 'cache-invalidation',

        // Tenant threshold - use queue when affecting this many or more tenants
        'tenant_threshold' => 50,

        // Connection to use for cache invalidation jobs
        'connection' => env('QUEUE_CONNECTION', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for cache invalidation monitoring and logging.
    |
    */
    'monitoring' => [
        // Log all cache invalidations (useful for debugging)
        'log_invalidations' => env('CACHE_LOG_INVALIDATIONS', false),

        // Log only bidirectional invalidations
        'log_bidirectional_only' => env('CACHE_LOG_BIDIRECTIONAL_ONLY', true),

        // Track invalidation metrics
        'track_metrics' => env('CACHE_TRACK_METRICS', true),
    ],
];
