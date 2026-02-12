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
    */
    'auto_index' => [
        'enabled' => true,
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
        'indices' => [
            // DEPRECATED: Old per-patient daily indices (will be migrated)
            // Run: php artisan wellmed-backbone:migrate-patient-integration-indices
            // After migration, these indices will be deleted
            'patient-dashboard-metrics' => [
                'retention_days' => 0,      // Delete all old indices
                'flushable' => true,
                'flush_documents' => true,
                'flush_indices' => true,    // Delete the indices entirely
                'description' => 'DEPRECATED - Old per-patient daily metrics (migrate to patient-satu-sehat-integration)',
                'deprecated' => true,
            ],

            // NEW: Patient Satu Sehat Integration (single index per tenant)
            // This is NOT time-based - stores current state of each patient
            // No retention needed - data persists as long as patient exists
            'patient-satu-sehat-integration' => [
                'retention_days' => null,   // No retention - data persists forever
                'flushable' => false,       // Never auto-flush
                'flush_documents' => false,
                'flush_indices' => false,
                'description' => 'Patient Satu Sehat integration status (1 doc per patient, current state)',
            ],

            // Main dashboard metrics (shared index per tenant)
            'dashboard-metrics-daily' => [
                'retention_days' => env('ELASTICSEARCH_DASHBOARD_DAILY_RETENTION_DAYS', 30),
                'flushable' => true,
                'flush_documents' => true,
                'flush_indices' => false,   // Keep index structure
                'description' => 'Tenant-level daily dashboard aggregations',
            ],

            'dashboard-metrics-weekly' => [
                'retention_days' => env('ELASTICSEARCH_DASHBOARD_WEEKLY_RETENTION_DAYS', 90),
                'flushable' => true,
                'flush_documents' => true,
                'flush_indices' => false,
                'description' => 'Tenant-level weekly dashboard aggregations',
            ],

            'dashboard-metrics-monthly' => [
                'retention_days' => env('ELASTICSEARCH_DASHBOARD_MONTHLY_RETENTION_DAYS', 365),
                'flushable' => true,
                'flush_documents' => true,
                'flush_indices' => false,
                'description' => 'Tenant-level monthly dashboard aggregations',
            ],

            'dashboard-metrics-yearly' => [
                'retention_days' => env('ELASTICSEARCH_DASHBOARD_YEARLY_RETENTION_DAYS', 1825), // 5 years
                'flushable' => false,
                'flush_documents' => false,
                'flush_indices' => false,
                'description' => 'Tenant-level yearly dashboard aggregations (keep forever)',
            ],

            // Visit registration queue (real-time data)
            'visit-registration-queue' => [
                'retention_days' => env('ELASTICSEARCH_QUEUE_RETENTION_DAYS', 1),
                'flushable' => true,
                'flush_documents' => true,
                'flush_indices' => false,
                'description' => 'Real-time visit queue data',
            ],
        ],

        // Schedule for automatic flush (runs daily at 2 AM)
        'schedule' => [
            'enabled' => env('ELASTICSEARCH_RETENTION_SCHEDULE_ENABLED', true),
            'cron' => '0 2 * * *',
            'queue' => 'elasticsearch',
        ],
    ],
];
