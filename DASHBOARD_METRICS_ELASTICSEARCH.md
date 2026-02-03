# Dashboard Metrics Elasticsearch Integration

This guide explains how to use the Dashboard Metrics Elasticsearch integration for storing and retrieving dashboard statistics with daily, monthly, and yearly aggregations.

## Table of Contents

- [Overview](#overview)
- [Setup](#setup)
- [Data Structure](#data-structure)
- [Usage Examples](#usage-examples)
- [API Reference](#api-reference)
- [Best Practices](#best-practices)

## Overview

The Dashboard Metrics system provides:
- **Three period types**: `daily`, `monthly`, `yearly`
- **Automatic tenant isolation**: All metrics are stored per tenant
- **Rich statistics**: Patient counts, revenue, pending items, queue services, and diagnosis data
- **Efficient querying**: Optimized Elasticsearch queries with aggregations
- **Flexible filtering**: Filter by date, year, month, day, tenant, workspace

## Setup

### 1. Install Elasticsearch Template

First, install the index template in Elasticsearch:

```bash
# Upload the template to Elasticsearch
curl -X PUT "http://localhost:9200/_index_template/dashboard-metrics-template" \
  -H 'Content-Type: application/json' \
  -d @elasticsearch/templates/dashboard-metrics-template.json
```

### 2. Verify Template Installation

```bash
# Check if template is installed
curl -X GET "http://localhost:9200/_index_template/dashboard-metrics-template"
```

### 3. Enable Elasticsearch in Configuration

Update your `.env.backbone`:

```env
ELASTICSEARCH_ENABLED=true
ELASTICSEARCH_HOSTS=localhost:9200
ELASTICSEARCH_USERNAME=elastic
ELASTICSEARCH_PASSWORD=your_password
ELASTICSEARCH_PREFIX=clinic_4
```

## Data Structure

### Input Data Format

```php
$dashboardData = [
    'statistics' => [
        'patients' => [
            'count' => 18,
            'change' => 4,
            'change_type' => 'increase', // or 'decrease'
            'percentage_change' => 28.57
        ],
        'new_patients' => [
            'count' => 4,
            'change' => 1,
            'change_type' => 'increase',
            'percentage_change' => 33.33
        ],
        'revenue' => [
            'count' => 21250000,
            'change' => 250000,
            'change_type' => 'increase',
            'percentage_change' => 1.19
        ],
        'treatment' => [
            'count' => 31,
            'change' => 8,
            'change_type' => 'increase',
            'percentage_change' => 34.78
        ]
    ],
    'motivational_stats' => [
        'today' => 40,
        'yesterday' => 38,
        'target' => 50,
        'percentage' => 80.0
    ],
    'pending_items' => [
        'unsigned_visits' => 4,
        'unsynced_patients' => 9,
        'incomplete_diagnosis' => 11
    ],
    'queue_services' => [
        [
            'service_id' => 'poli-umum',
            'service_name' => 'Poli Umum',
            'current_queue' => 12,
            'waiting' => 8,
            'serving' => 4
        ],
        [
            'service_id' => 'poli-gigi',
            'service_name' => 'Poli Gigi',
            'current_queue' => 5,
            'waiting' => 3,
            'serving' => 2
        ]
    ],
    'diagnosis_treatment' => [
        [
            'patient_id' => 'P001',
            'patient_name' => 'Budi Santoso',
            'code' => 'A09',
            'type' => 'Diagnosa',
            'description' => 'Gastroenteritis',
            'poli' => 'Poli Umum',
            'doctor_id' => 'D001',
            'doctor_name' => 'Dr. Ahmad'
        ]
    ],
    'aggregation_period' => [
        'start_date' => '2024-12-29',
        'end_date' => '2024-12-29',
        'label' => 'Today'
    ],
    'metadata' => [
        'created_by' => 'system',
        'version' => '1.0'
    ]
];
```

## Usage Examples

### Basic Usage

#### 1. Store Daily Metrics

```php
use Projects\WellmedBackbone\Services\DashboardMetricsService;

$metricsService = app(DashboardMetricsService::class);

// Store today's dashboard metrics
$result = $metricsService->store($dashboardData, 'daily');

if ($result['success']) {
    echo "Metrics stored with ID: " . $result['id'];
} else {
    echo "Error: " . $result['error'];
}
```

#### 2. Store Monthly Metrics

```php
// Store monthly aggregated metrics
$monthlyData = [
    'statistics' => [
        'patients' => [
            'count' => 540, // Total for the month
            'change' => 45,
            'change_type' => 'increase',
            'percentage_change' => 9.09
        ],
        // ... other statistics
    ],
    'aggregation_period' => [
        'start_date' => '2024-12-01',
        'end_date' => '2024-12-31',
        'label' => 'December 2024'
    ]
];

$result = $metricsService->store($monthlyData, 'monthly');
```

#### 3. Store Yearly Metrics

```php
// Store yearly aggregated metrics
$yearlyData = [
    'statistics' => [
        'patients' => [
            'count' => 6500, // Total for the year
            'change' => 520,
            'change_type' => 'increase',
            'percentage_change' => 8.7
        ],
        // ... other statistics
    ],
    'aggregation_period' => [
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'label' => '2024'
    ]
];

$result = $metricsService->store($yearlyData, 'yearly');
```

### Retrieving Metrics

#### 1. Get Latest Daily Metrics

```php
// Get today's metrics (latest for current tenant)
$result = $metricsService->get('daily');

if ($result['success'] && $result['data']) {
    $metrics = $result['data'];

    echo "Total Patients: " . $metrics['statistics']['patients']['count'];
    echo "Revenue: " . $metrics['statistics']['revenue']['count'];
    echo "Pending Items: " . $metrics['pending_items']['unsigned_visits'];
}
```

#### 2. Get Metrics for Specific Date

```php
// Get metrics for a specific date
$result = $metricsService->get('daily', [
    'date' => '2024-12-29'
]);
```

#### 3. Get Monthly Metrics

```php
// Get metrics for specific month
$result = $metricsService->get('monthly', [
    'year' => 2024,
    'month' => 12
]);
```

#### 4. Get Yearly Metrics

```php
// Get metrics for specific year
$result = $metricsService->get('yearly', [
    'year' => 2024
]);
```

### Advanced Queries

#### 1. Get Metrics for Date Range

```php
use Carbon\Carbon;

$startDate = Carbon::parse('2024-12-01');
$endDate = Carbon::parse('2024-12-31');

$result = $metricsService->getRange('daily', $startDate, $endDate);

if ($result['success']) {
    foreach ($result['data'] as $dailyMetrics) {
        echo "Date: " . $dailyMetrics['date'];
        echo "Patients: " . $dailyMetrics['statistics']['patients']['count'];
    }

    echo "Total records: " . $result['total'];
}
```

#### 2. Get Multiple Records

```php
// Get last 7 days of metrics
$result = $metricsService->get('daily', [
    'size' => 7 // Get 7 most recent records
]);
```

#### 3. Aggregate Statistics

```php
// Get total revenue for the month
$result = $metricsService->aggregate(
    'daily',           // Period type
    'revenue',         // Metric name
    'sum',            // Aggregation type (sum, avg, min, max)
    [
        'year' => 2024,
        'month' => 12
    ]
);

if ($result['success']) {
    echo "Total monthly revenue: " . $result['value'];
}
```

```php
// Get average patient count for the year
$result = $metricsService->aggregate(
    'monthly',
    'patients',
    'avg',
    ['year' => 2024]
);

echo "Average monthly patients: " . $result['value'];
```

### Controller Integration Example

```php
<?php

namespace Projects\WellmedGateway\Controllers\API\Dashboard;

use Projects\WellmedBackbone\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected DashboardMetricsService $metricsService;

    public function __construct(DashboardMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Get dashboard data for the frontend
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'today');

        $result = match($filter) {
            'today' => $this->metricsService->get('daily'),
            'this-week' => $this->getWeeklyMetrics(),
            'this-month' => $this->metricsService->get('monthly', [
                'year' => now()->year,
                'month' => now()->month
            ]),
            default => $this->metricsService->get('daily')
        };

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve metrics'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * Get weekly metrics by aggregating daily data
     */
    protected function getWeeklyMetrics()
    {
        $startDate = now()->startOfWeek();
        $endDate = now()->endOfWeek();

        return $this->metricsService->getRange('daily', $startDate, $endDate);
    }

    /**
     * Store current dashboard snapshot
     */
    public function snapshot(Request $request)
    {
        // Calculate real-time metrics from database
        $dashboardData = $this->calculateDashboardMetrics();

        // Store in Elasticsearch
        $result = $this->metricsService->store($dashboardData, 'daily');

        return response()->json($result);
    }

    /**
     * Calculate dashboard metrics from database
     */
    protected function calculateDashboardMetrics(): array
    {
        // Example calculation
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $patientsToday = \DB::table('visit_patients')
            ->whereDate('created_at', $today)
            ->count();

        $patientsYesterday = \DB::table('visit_patients')
            ->whereDate('created_at', $yesterday)
            ->count();

        return [
            'statistics' => [
                'patients' => [
                    'count' => $patientsToday,
                    'change' => $patientsToday - $patientsYesterday,
                    'change_type' => $patientsToday >= $patientsYesterday ? 'increase' : 'decrease',
                    'percentage_change' => $patientsYesterday > 0
                        ? (($patientsToday - $patientsYesterday) / $patientsYesterday) * 100
                        : 0
                ],
                // ... calculate other statistics
            ],
            // ... other dashboard data
        ];
    }
}
```

### Scheduled Metrics Collection

Create a scheduled task to automatically collect metrics:

```php
<?php

namespace Projects\WellmedBackbone\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Projects\WellmedBackbone\Services\DashboardMetricsService;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Store daily metrics every day at midnight
        $schedule->call(function () {
            $metricsService = app(DashboardMetricsService::class);
            $dashboardData = $this->calculateDailyMetrics();
            $metricsService->store($dashboardData, 'daily');
        })->daily()->at('00:00');

        // Store monthly metrics on the first day of each month
        $schedule->call(function () {
            $metricsService = app(DashboardMetricsService::class);
            $dashboardData = $this->calculateMonthlyMetrics();
            $metricsService->store($dashboardData, 'monthly');
        })->monthlyOn(1, '00:00');

        // Store yearly metrics on January 1st
        $schedule->call(function () {
            $metricsService = app(DashboardMetricsService::class);
            $dashboardData = $this->calculateYearlyMetrics();
            $metricsService->store($dashboardData, 'yearly');
        })->yearlyOn(1, 1, '00:00');
    }
}
```

### Delete Metrics

```php
// Delete metrics for a specific date
$result = $metricsService->delete('daily', [
    'date' => '2024-12-29'
]);

echo "Deleted " . $result['deleted'] . " documents";

// Delete all metrics for a month
$result = $metricsService->delete('monthly', [
    'year' => 2024,
    'month' => 12
]);
```

## API Reference

### DashboardMetricsService Methods

#### `store(array $data, string $periodType = 'daily', ?int $tenantId = null, ?int $workspaceId = null): array`

Store dashboard metrics in Elasticsearch.

**Parameters:**
- `$data` - Dashboard metrics data (see Data Structure)
- `$periodType` - One of: `'daily'`, `'monthly'`, `'yearly'`
- `$tenantId` - Optional tenant ID (defaults to current tenant)
- `$workspaceId` - Optional workspace ID (defaults to current workspace)

**Returns:**
```php
[
    'success' => true,
    'id' => 'document_id',
    'index' => 'index_name'
]
```

#### `get(string $periodType = 'daily', array $filters = []): array`

Retrieve latest dashboard metrics.

**Parameters:**
- `$periodType` - One of: `'daily'`, `'monthly'`, `'yearly'`
- `$filters` - Optional filters:
  - `date` - Specific date (YYYY-MM-DD)
  - `year` - Year (integer)
  - `month` - Month (1-12)
  - `day` - Day (1-31)
  - `size` - Number of records to retrieve (default: 1)
  - `tenant_id` - Tenant ID
  - `workspace_id` - Workspace ID

**Returns:**
```php
[
    'success' => true,
    'data' => [...], // Metrics data
    'total' => 1
]
```

#### `getRange(string $periodType, Carbon $startDate, Carbon $endDate, array $additionalFilters = []): array`

Retrieve metrics for a date range.

**Parameters:**
- `$periodType` - Period type
- `$startDate` - Start date (Carbon instance)
- `$endDate` - End date (Carbon instance)
- `$additionalFilters` - Additional filters

**Returns:**
```php
[
    'success' => true,
    'data' => [...], // Array of metrics
    'total' => 10
]
```

#### `aggregate(string $periodType, string $metric, string $aggregation = 'sum', array $filters = []): array`

Aggregate statistics for a metric.

**Parameters:**
- `$periodType` - Period type
- `$metric` - Metric name (`'patients'`, `'new_patients'`, `'revenue'`, `'treatment'`)
- `$aggregation` - Aggregation type (`'sum'`, `'avg'`, `'min'`, `'max'`)
- `$filters` - Filters (year, month, day, etc.)

**Returns:**
```php
[
    'success' => true,
    'value' => 12345.67,
    'total_documents' => 30
]
```

#### `delete(string $periodType, array $filters = []): array`

Delete metrics matching filters.

**Parameters:**
- `$periodType` - Period type
- `$filters` - Filters to match documents for deletion

**Returns:**
```php
[
    'success' => true,
    'deleted' => 5
]
```

## Best Practices

### 1. Period Type Selection

- **Daily**: Store detailed daily snapshots for recent trends (last 30-90 days)
- **Monthly**: Store aggregated monthly data for historical analysis (last 1-2 years)
- **Yearly**: Store yearly summaries for long-term trends (all years)

### 2. Data Retention

Implement data retention policies:

```php
// Delete daily metrics older than 90 days
$cutoffDate = now()->subDays(90);
$metricsService->delete('daily', [
    'date' => ['lte' => $cutoffDate->toDateString()]
]);
```

### 3. Caching Strategy

Cache frequently accessed metrics:

```php
use Illuminate\Support\Facades\Cache;

$cacheKey = "dashboard:daily:" . now()->toDateString();

$metrics = Cache::remember($cacheKey, 3600, function () use ($metricsService) {
    $result = $metricsService->get('daily');
    return $result['data'] ?? null;
});
```

### 4. Error Handling

Always check for success and handle errors:

```php
$result = $metricsService->store($data, 'daily');

if (!$result['success']) {
    Log::error('Failed to store metrics', [
        'error' => $result['error'],
        'data' => $data
    ]);

    // Fallback: Store in database or queue for retry
}
```

### 5. Multi-Tenant Isolation

The service automatically handles tenant isolation. Always ensure tenant context is set:

```php
// Tenant context is automatically used
MicroTenant::tenantImpersonate($tenantId);

$result = $metricsService->store($data, 'daily');
```

### 6. Performance Optimization

For large datasets, use aggregations instead of fetching all documents:

```php
// Good: Use aggregation
$totalRevenue = $metricsService->aggregate('daily', 'revenue', 'sum', [
    'year' => 2024,
    'month' => 12
]);

// Avoid: Fetching all records and summing in PHP
$allMetrics = $metricsService->getRange('daily', $start, $end);
$total = array_sum(array_column($allMetrics['data'], 'statistics.revenue.count'));
```

## Troubleshooting

### Check if Elasticsearch is running

```bash
curl http://localhost:9200
```

### Check index exists

```bash
curl http://localhost:9200/development.dashboard-metrics-daily/_count
```

### View sample documents

```bash
curl http://localhost:9200/development.dashboard-metrics-daily/_search?size=1&pretty
```

### Enable debug logging

Update `config/logging.php`:

```php
'channels' => [
    'elasticsearch' => [
        'driver' => 'daily',
        'path' => storage_path('logs/elasticsearch.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

## License

This integration is part of the Wellmed healthcare management system.
