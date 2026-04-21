<?php

use Hanafalah\LaravelSupport\Jobs\ElasticJob;

return [
    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable Elasticsearch globally. When disabled, all queries
    | will fall back to standard database queries.
    |
    */
    'enabled' => env('ELASTICSEARCH_ENABLED', true),
    // 'enabled' => false,

    'job_class' => ElasticJob::class,

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Hosts
    |--------------------------------------------------------------------------
    |
    | The Elasticsearch host(s) to connect to. Can be a single host or
    | comma-separated list of hosts.
    |
    */
    'hosts' => [env('ELASTICSEARCH_HOSTS', 'localhost:9200')],

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Credentials
    |--------------------------------------------------------------------------
    |
    | Username and password for Elasticsearch authentication.
    |
    */
    'username' => env('ELASTICSEARCH_USERNAME', 'elastic'),
    'password' => env('ELASTICSEARCH_PASSWORD', 'password'),

    /*
    |--------------------------------------------------------------------------
    | Dynamic Index Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for all Elasticsearch indexes. This can be set dynamically at
    | runtime (e.g., based on tenant ID) to support multi-tenancy.
    | Defaults to APP_ENV if not specified.
    |
    */
    'prefix' => env('ELASTICSEARCH_PREFIX', env('APP_ENV', 'development')),

    /*
    |--------------------------------------------------------------------------
    | Index Name Separator
    |--------------------------------------------------------------------------
    |
    | Character used to separate prefix from index name.
    | Example: prefix "tenant-001" + separator "." + index "patient" = "tenant-001.patient"
    |
    */
    'separator' => '.',

    /*
    |--------------------------------------------------------------------------
    | Query Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Elasticsearch query behavior.
    |
    */
    'query_timeout' => 5, // seconds

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | Automatically disable Elasticsearch after consecutive failures to prevent
    | cascading failures. The system will attempt to re-enable after cooldown.
    |
    */
    'circuit_breaker' => [
        'enabled' => true,
        'failure_threshold' => 5,
        'cooldown_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Indexing
    |--------------------------------------------------------------------------
    |
    | Automatically index model changes (create/update/delete) to Elasticsearch
    | using Laravel observers and queued jobs.
    |
    | sync: When true, indexing happens synchronously (immediate). When false,
    |       indexing is queued to RabbitMQ (async). Use sync mode for critical
    |       data that needs immediate consistency (e.g., status changes).
    |
    |       Models can override this globally by setting 'sync' in their
    |       $elastic_config property:
    |       protected array $elastic_config = [
    |           'enabled' => true,
    |           'sync' => true,  // Override global config for this model
    |           'index_name' => 'my_index',
    |       ];
    |
    */
    'auto_index' => [
        'enabled' => true,
        'sync' => true,  // Default: synchronous mode for immediate consistency
        // 'sync' => env('ELASTICSEARCH_SYNC', false),  // Use this to control via .env
        'queue' => 'elasticsearch',
        'connection' => 'rabbitmq',
    ],

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Logging
    |--------------------------------------------------------------------------
    |
    | Log Elasticsearch operations to the elasticsearch_logs database table.
    | This allows tracking of all ES sync operations for audit/debugging.
    |
    | Dashboard indices (containing 'dashboard') will update the same log record
    | on each sync. Reporting indices will create new log records.
    |
    */
    'logging' => [
        'enabled' => env('ELASTICSEARCH_LOGGING_ENABLED', true),
        'dashboard_prefixes' => [
            'dashboard-metrics',
            'dashboard',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Index Retention Policy
    |--------------------------------------------------------------------------
    |
    | Configure automatic cleanup of old Elasticsearch indices.
    | This helps prevent index bloat, especially for per-patient daily indices.
    |
    | retention_days: Number of days to keep data (e.g., 2 = today + yesterday)
    | flushable: Whether this index type can be automatically flushed
    | flush_schedule: Cron expression for automatic flush (null = manual only)
    |
    */
    'retention' => [
        'enabled' => env('ELASTICSEARCH_RETENTION_ENABLED', true),

        // Default retention for all indices (in days)
        'default_days' => env('ELASTICSEARCH_RETENTION_DAYS', 7),

        // Per-index type retention configuration
        'indices' => [],
        'schedule' => [
            'enabled' => env('ELASTICSEARCH_RETENTION_SCHEDULE_ENABLED', true),
            'cron' => '0 2 * * *',
            'queue' => 'elasticsearch',
        ],
    ],
];
