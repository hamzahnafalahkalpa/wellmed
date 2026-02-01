# Dashboard Metrics - Quick Start Guide

Get started with Dashboard Metrics Elasticsearch integration in 5 minutes.

## Prerequisites

- Elasticsearch 7.x or 8.x running
- Laravel application configured
- Access to command line

## Step 1: Install Elasticsearch Template

Run the setup script:

```bash
cd /var/www/projects/wellmed

# Make sure Elasticsearch is running
curl http://localhost:9200

# Run setup script
./elasticsearch/scripts/setup-dashboard-metrics.sh
```

If you need custom Elasticsearch credentials:

```bash
ELASTICSEARCH_HOST=localhost:9200 \
ELASTICSEARCH_USER=elastic \
ELASTICSEARCH_PASSWORD=your_password \
./elasticsearch/scripts/setup-dashboard-metrics.sh
```

## Step 2: Configure Environment

Update your `.env.backbone`:

```env
ELASTICSEARCH_ENABLED=true
ELASTICSEARCH_HOSTS=localhost:9200
ELASTICSEARCH_USERNAME=elastic
ELASTICSEARCH_PASSWORD=your_password
ELASTICSEARCH_PREFIX=clinic_4
```

## Step 3: Test the Integration

### Option A: Using Artisan Command

```bash
# Store sample daily metrics
docker exec -it wellmed-backbone php artisan dashboard:metrics:test store --period=daily

# Retrieve daily metrics
docker exec -it wellmed-backbone php artisan dashboard:metrics:test get --period=daily

# Get metrics for a specific date
docker exec -it wellmed-backbone php artisan dashboard:metrics:test get --period=daily --date=2024-12-29

# Get metrics for date range
docker exec -it wellmed-backbone php artisan dashboard:metrics:test range --period=daily --start-date=2024-12-01 --end-date=2024-12-31

# Aggregate total revenue
docker exec -it wellmed-backbone php artisan dashboard:metrics:test aggregate --period=daily --metric=revenue --agg=sum
```

### Option B: Using Tinker

```bash
docker exec -it wellmed-backbone php artisan tinker
```

Then in Tinker:

```php
// Load sample data
$sampleData = include('elasticsearch/examples/sample-dashboard-data.php');

// Get service
$service = app(\Projects\WellmedBackbone\Services\DashboardMetricsService::class);

// Store daily metrics
$result = $service->store($sampleData['daily'], 'daily');
print_r($result);

// Store monthly metrics
$result = $service->store($sampleData['monthly'], 'monthly');
print_r($result);

// Retrieve metrics
$result = $service->get('daily');
print_r($result['data']);

// Get range
$start = \Carbon\Carbon::parse('2024-12-01');
$end = \Carbon\Carbon::parse('2024-12-31');
$result = $service->getRange('daily', $start, $end);
echo "Found {$result['total']} records\n";

// Aggregate
$result = $service->aggregate('daily', 'revenue', 'sum');
echo "Total revenue: Rp " . number_format($result['value']) . "\n";
```

## Step 4: Integrate into Your Application

### Create a Controller

Create `projects/wellmed-gateway/src/Controllers/API/Dashboard/DashboardController.php`:

```php
<?php

namespace Projects\WellmedGateway\Controllers\API\Dashboard;

use Projects\WellmedBackbone\Services\DashboardMetricsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardMetricsService $metricsService;

    public function __construct(DashboardMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    public function index(Request $request)
    {
        $filter = $request->input('filter', 'today');
        $periodType = $request->input('period', 'daily');

        $result = $this->metricsService->get($periodType);

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

    public function snapshot()
    {
        // Calculate current dashboard data
        $dashboardData = $this->calculateCurrentMetrics();

        // Store in Elasticsearch
        $result = $this->metricsService->store($dashboardData, 'daily');

        return response()->json($result);
    }

    protected function calculateCurrentMetrics(): array
    {
        // Implement your logic here to calculate metrics from database
        return [
            'statistics' => [
                'patients' => ['count' => 0, 'change' => 0, 'change_type' => 'increase', 'percentage_change' => 0],
                // ... other metrics
            ],
            // ... rest of structure
        ];
    }
}
```

### Add Routes

In `routes/api-gateway.php`:

```php
Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::post('/snapshot', [DashboardController::class, 'snapshot']);
});
```

## Step 5: Schedule Automatic Collection

Add to your console kernel (`projects/wellmed-backbone/src/Console/Kernel.php`):

```php
protected function schedule(Schedule $schedule)
{
    // Store daily snapshot at midnight
    $schedule->call(function () {
        $service = app(\Projects\WellmedBackbone\Services\DashboardMetricsService::class);

        // Calculate metrics from your data
        $data = $this->calculateDailyMetrics();

        $service->store($data, 'daily');
    })->daily()->at('00:00');
}
```

## Verify Everything Works

### Check Elasticsearch

```bash
# Check if index exists
curl http://localhost:9200/_cat/indices?v | grep dashboard-metrics

# Count documents
curl http://localhost:9200/development.dashboard-metrics-daily/_count

# View sample document
curl http://localhost:9200/development.dashboard-metrics-daily/_search?size=1&pretty
```

### Check Logs

```bash
# View Elasticsearch logs
docker exec -it wellmed-backbone tail -f storage/logs/elasticsearch.log

# View Laravel logs
docker exec -it wellmed-backbone tail -f storage/logs/laravel.log
```

## Common Issues

### Issue: "Connection refused to Elasticsearch"

**Solution:**
- Ensure Elasticsearch is running: `curl http://localhost:9200`
- Check ELASTICSEARCH_HOST in .env
- Check firewall settings

### Issue: "Index template not found"

**Solution:**
- Run setup script again: `./elasticsearch/scripts/setup-dashboard-metrics.sh`
- Verify template exists: `curl http://localhost:9200/_index_template/dashboard-metrics-template`

### Issue: "Tenant ID is null"

**Solution:**
- Ensure tenant context is set: `MicroTenant::tenantImpersonate($tenantId)`
- Check multi-tenant middleware is working

## Next Steps

1. Read full documentation: `DASHBOARD_METRICS_ELASTICSEARCH.md`
2. Customize data structure for your needs
3. Implement real-time calculations
4. Set up data retention policies
5. Configure monitoring and alerts

## Example Frontend Integration

```javascript
// Fetch dashboard data
async function fetchDashboard(filter = 'today') {
  const response = await fetch(`/api/dashboard?filter=${filter}&period=daily`);
  const result = await response.json();

  if (result.success) {
    updateDashboard(result.data);
  }
}

// Store current snapshot
async function saveSnapshot() {
  const response = await fetch('/api/dashboard/snapshot', {
    method: 'POST'
  });
  const result = await response.json();

  console.log('Snapshot saved:', result);
}
```

## Support

- Full documentation: `DASHBOARD_METRICS_ELASTICSEARCH.md`
- Template file: `elasticsearch/templates/dashboard-metrics-template.json`
- Sample data: `elasticsearch/examples/sample-dashboard-data.php`
- Service class: `projects/wellmed-backbone/src/Services/DashboardMetricsService.php`

## Performance Tips

1. **Cache frequently accessed data:**
   ```php
   Cache::remember('dashboard:today', 300, function() {
       return $metricsService->get('daily');
   });
   ```

2. **Use aggregations for large datasets:**
   ```php
   // Good: Use Elasticsearch aggregation
   $total = $service->aggregate('daily', 'revenue', 'sum');

   // Avoid: Fetching all records
   $all = $service->getRange(...);
   ```

3. **Implement data retention:**
   ```php
   // Delete old daily metrics (keep 90 days)
   $service->delete('daily', [
       'date' => ['lte' => now()->subDays(90)->toDateString()]
   ]);
   ```

That's it! You're now ready to use Dashboard Metrics with Elasticsearch. 🎉
