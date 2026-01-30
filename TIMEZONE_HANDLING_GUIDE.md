# Timezone Handling Guide for Wellmed Multi-Tenant System

This guide explains the optimized timezone handling solution for Wellmed's multi-tenant Laravel Octane application using Workspace-based timezone configuration.

## Overview

The solution provides:
- **Octane-safe** - No static variables, per-request timezone management
- **Workspace-based** - Each workspace has its own timezone setting
- **Automatic conversion** - Dates stored in UTC, displayed in workspace timezone
- **Laravel-native** - Uses middleware and custom casts
- **Tenant-aware** - Timezone set during tenant initialization

## Architecture

```
Request
  ↓
Tenant Initialization (MicroTenantServiceProvider)
  ├─ Load Workspace
  └─ Set workspace timezone from: $workspace->setting['timezone']['name']
  ↓
Middleware (Apply timezone to request)
  ↓
Model Input → Custom Cast → Database (UTC)
Model Output ← Custom Cast ← Database (UTC)
  ↓
API Resource (Workspace TZ)
  ↓
Response (Workspace TZ)
```

## Workspace Timezone Structure

Your workspace model stores timezone in the `props` → `setting` → `timezone`:

```php
$workspace->setting = [
    'timezone_id' => 123,
    'timezone' => [
        'id' => 123,
        'name' => 'Asia/Jakarta',  // ← This is used
        'label' => 'WIB (UTC+7)',
        'flag' => '🇮🇩',
    ],
    // ... other settings
];
```

## Setup Instructions

### 1. Register Middleware

Add the timezone middleware to your HTTP kernel:

**In `app/Http/Kernel.php`:**

```php
protected $middlewareGroups = [
    'api' => [
        // ... other middleware
        \Hanafalah\MicroTenant\Middleware\SetWorkspaceTimezone::class,
    ],
];
```

**Or in `bootstrap/app.php` (Laravel 12):**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \Hanafalah\MicroTenant\Middleware\SetWorkspaceTimezone::class,
    ]);
})
```

### 2. Configure Default Timezone in `.env`

```env
APP_TIMEZONE=UTC                    # Database timezone (always UTC)
# APP_CLIENT_TIMEZONE is set automatically from workspace settings
```

### 3. Ensure Workspace Has Timezone Data

Make sure your workspace seeder or creation logic includes timezone:

```php
use Hanafalah\ModuleWorkspace\Models\Workspace\Workspace;

$workspace = Workspace::create([
    'name' => 'My Clinic',
    'owner_id' => $user->id,
    'setting' => [
        'timezone_id' => 123,
        'timezone' => [
            'id' => 123,
            'name' => 'Asia/Jakarta',  // CRITICAL: Must be valid PHP timezone
            'label' => 'WIB (UTC+7)',
            'flag' => '🇮🇩',
        ],
    ],
]);
```

### 4. Update Models to Use TimezonedDateTime Cast

**Before (manual timezone handling):**

```php
class Appointment extends Model
{
    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
```

**After (automatic timezone handling):**

```php
use Hanafalah\LaravelSupport\Casts\TimezonedDateTime;

class Appointment extends Model
{
    protected $casts = [
        'scheduled_at' => TimezonedDateTime::class,
        'completed_at' => TimezonedDateTime::class,
        'created_at' => TimezonedDateTime::class,  // Also for timestamps!
        'updated_at' => TimezonedDateTime::class,
    ];
}
```

## How It Works

### 1. Tenant Initialization (MicroTenantServiceProvider)

When a tenant is initialized, the workspace timezone is automatically loaded:

```php
// In MicroTenantServiceProvider@setWorkspaceTimezone()
$workspace = $tenant->reference;

// Extract timezone from workspace settings
$timezone = $workspace->setting['timezone']['name'] ?? 'UTC';

// Set for this request
config()->set('app.client_timezone', $timezone);
```

**This happens automatically - you don't need to do anything!**

### 2. Middleware: SetWorkspaceTimezone

The middleware applies the timezone in this priority order:

1. **User's personal timezone** (if `$user->timezone` is set)
2. **Workspace timezone** (from `config('app.client_timezone')`)
3. **App default** (`config('app.timezone')`)

### 3. Custom Cast: TimezonedDateTime

**On Get (Database → API Response):**
- Database has: `2024-01-30 14:00:00` (UTC)
- Cast converts to: `2024-01-30 21:00:00` (Asia/Jakarta, +7 hours)
- API returns: `"scheduled_at": "2024-01-30 21:00:00"`

**On Set (API Request → Database):**
- Client sends: `2024-01-30 21:00:00` (assumed workspace timezone)
- Cast converts to: `2024-01-30 14:00:00` (UTC)
- Database stores: `2024-01-30 14:00:00`

### 4. Query Scopes: scopeWithParameters

The `scopeWithParameters` method automatically converts date filters to UTC:

```php
// Client sends: search_scheduled_at=2024-01-30
// Interpreted as: 2024-01-30 in workspace timezone (Asia/Jakarta)
// Converted to UTC range for query:
//   2024-01-29 17:00:00 to 2024-01-30 16:59:59 (UTC)

Appointment::withParameters()->get();
```

## Usage Examples

### Creating Records

```php
// Client code (assumes workspace timezone - Asia/Jakarta)
$appointment = Appointment::create([
    'scheduled_at' => '2024-01-30 14:00:00'
]);

// Database stores: 2024-01-30 07:00:00 (UTC)
// API returns: "scheduled_at": "2024-01-30 14:00:00" (Asia/Jakarta)
```

### Querying Records

```php
// Using search parameters (workspace timezone)
$appointments = Appointment::withParameters()->get();
// Input: search_scheduled_at=2024-01-30
// Queries: WHERE scheduled_at BETWEEN '2024-01-29 17:00:00' AND '2024-01-30 16:59:59' (UTC)

// Direct query
$appointments = Appointment::where('scheduled_at', '>=', '2024-01-30 14:00:00')->get();
// Automatically converts to UTC: WHERE scheduled_at >= '2024-01-30 07:00:00'
```

### API Responses

```php
// Controller
return AppointmentResource::collection($appointments);

// Response automatically shows dates in workspace timezone
{
    "id": 1,
    "scheduled_at": "2024-01-30 14:00:00",  // Asia/Jakarta
    "created_at": "2024-01-15 09:30:00"     // Asia/Jakarta
}
```

### Different Workspaces, Different Timezones

```bash
# Workspace 1: Jakarta Clinic (Asia/Jakarta - UTC+7)
GET /api/clinic_4/appointments/1
# Returns: "scheduled_at": "2024-01-30 14:00:00"

# Workspace 2: Tokyo Clinic (Asia/Tokyo - UTC+9)
GET /api/clinic_5/appointments/1
# Returns: "scheduled_at": "2024-01-30 16:00:00"  (same moment, different display)

# Workspace 3: London Clinic (Europe/London - UTC+0)
GET /api/clinic_6/appointments/1
# Returns: "scheduled_at": "2024-01-30 07:00:00"  (same moment, different display)
```

## Migration Guide

### For Existing Models

1. **Update casts** from `'datetime'` to `TimezonedDateTime::class`
2. **Test** with different workspace timezones
3. **No data migration needed** - Database already stores in UTC

### For New Models

Use `TimezonedDateTime::class` from the start for all datetime fields.

### Gradual Migration

You can migrate gradually:
- Old models continue using `HasDateNormalize` trait
- New models use `TimezonedDateTime` cast
- Both approaches work simultaneously

## Benefits Over Previous Approach

| Aspect | Old Approach | New Approach |
|--------|--------------|--------------|
| **Octane Safety** | ❌ Static variables persist | ✅ Per-request, no static vars |
| **Complexity** | ❌ Manual conversion everywhere | ✅ Automatic via cast |
| **Workspace Integration** | ⚠️ Manual config reading | ✅ Automatic from workspace |
| **Maintainability** | ❌ Scattered timezone logic | ✅ Centralized in 2 files |
| **Laravel-native** | ❌ Custom implementation | ✅ Standard Laravel patterns |
| **Query Performance** | ❌ Complex nested calculations | ✅ Simple UTC conversion |
| **Developer Experience** | ❌ Must remember to convert | ✅ Automatic, transparent |

## Troubleshooting

### Dates showing in wrong timezone

**Check:**
1. Middleware is registered in HTTP Kernel
2. Model uses `TimezonedDateTime` cast
3. Workspace has `setting['timezone']['name']` set correctly
4. Timezone name is valid (check with `timezone_identifiers_list()`)

**Debug:**
```php
// In your controller
dd(config('app.client_timezone')); // Should show workspace timezone
dd(date_default_timezone_get());   // Should match workspace timezone
```

### Queries not filtering correctly

**Check:**
1. Using `scopeWithParameters()` for search filters
2. Date format is correct (Y-m-d or Y-m-d H:i:s)
3. Database timezone is UTC

**Debug:**
```php
// Enable query log
DB::enableQueryLog();
Appointment::withParameters()->get();
dd(DB::getQueryLog()); // Check the WHERE clause
```

### Workspace timezone not loading

**Check:**
1. `MicroTenantServiceProvider` is registered
2. Workspace `setting` has correct structure
3. Tenant initialization is working

**Debug:**
```php
// In TenancyBootstrapped event
$workspace = tenancy()->tenant->reference;
dd($workspace->setting); // Should show timezone data
```

## Testing

### Unit Test Example

```php
public function test_workspace_timezone_conversion()
{
    // Create tenant with Jakarta timezone
    $workspace = Workspace::factory()->create([
        'setting' => [
            'timezone_id' => 123,
            'timezone' => [
                'name' => 'Asia/Jakarta',
                'label' => 'WIB (UTC+7)',
            ],
        ],
    ]);

    tenancy()->initialize($workspace->tenant);

    // Config should be set
    $this->assertEquals('Asia/Jakarta', config('app.client_timezone'));

    $appointment = Appointment::create([
        'scheduled_at' => '2024-01-30 14:00:00'
    ]);

    // Database should store in UTC
    $this->assertDatabaseHas('appointments', [
        'scheduled_at' => '2024-01-30 07:00:00'
    ]);

    // Model should return in workspace timezone
    $this->assertEquals(
        '2024-01-30 14:00:00',
        $appointment->scheduled_at->format('Y-m-d H:i:s')
    );
}
```

## Performance Considerations

- Timezone conversion is lightweight (microseconds per field)
- No additional database queries
- Works efficiently with Octane's long-running workers
- Request-scoped, automatically garbage collected
- Workspace timezone cached in config per request

## Supported Timezones

All PHP timezone identifiers are supported. Common ones in Indonesia/Asia:

- `UTC` - Coordinated Universal Time
- `Asia/Jakarta` - Indonesia WIB (UTC+7)
- `Asia/Makassar` - Indonesia WITA (UTC+8)
- `Asia/Jayapura` - Indonesia WIT (UTC+9)
- `Asia/Bangkok` - Thailand (UTC+7)
- `Asia/Singapore` - Singapore (UTC+8)
- `Asia/Tokyo` - Japan (UTC+9)
- `Asia/Kuala_Lumpur` - Malaysia (UTC+8)

Full list: https://www.php.net/manual/en/timezones.php

## Key Files

- **Middleware**: `repositories/microtenant/src/Middleware/SetWorkspaceTimezone.php`
- **Custom Cast**: `repositories/laravel-support/src/Casts/TimezonedDateTime.php`
- **Provider**: `app/Providers/MicroTenantServiceProvider.php` (method: `setWorkspaceTimezone`)
- **Trait**: `repositories/laravel-support/src/Concerns/Support/HasConfigDatabase.php` (method: `timezoneCalculation`)

## Support

For issues or questions, please refer to:
- Laravel Octane documentation: https://laravel.com/docs/octane
- Carbon documentation: https://carbon.nesbot.com/docs/
- PHP timezone support: https://www.php.net/manual/en/timezones.php
- Wellmed MicroTenant package documentation
