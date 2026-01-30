# Timezone Handling Example - Quick Start

This is a quick reference for implementing the new Octane-safe timezone handling in your Wellmed models.

## 1. Register Middleware (One Time Setup)

In `bootstrap/app.php`:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Hanafalah\MicroTenant\Middleware\SetWorkspaceTimezone::class,
        ]);
    })
    // ... rest of your config
```

**That's it for setup!** The workspace timezone is automatically loaded from `$workspace->setting['timezone']['name']` during tenant initialization.

## 2. Update Your Model

**Before:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'scheduled_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
```

**After:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Hanafalah\LaravelSupport\Casts\TimezonedDateTime;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'scheduled_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => TimezonedDateTime::class,  // Changed
        'completed_at' => TimezonedDateTime::class,  // Changed
        'created_at' => TimezonedDateTime::class,    // Added
        'updated_at' => TimezonedDateTime::class,    // Added
    ];
}
```

## 3. That's It!

Now your dates will automatically convert between UTC (database) and workspace timezone (API).

## Example Usage

### Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
        ]);

        // Client sends: "scheduled_at": "2024-01-30 14:00:00" (Asia/Jakarta)
        $appointment = Appointment::create($validated);
        // Database stores: "2024-01-30 07:00:00" (UTC)

        return response()->json($appointment);
        // API returns: "scheduled_at": "2024-01-30 14:00:00" (Asia/Jakarta)
    }

    public function index(Request $request)
    {
        // Automatic timezone conversion in queries
        $appointments = Appointment::withParameters()->get();

        // If user searches: search_scheduled_at=2024-01-30
        // Query converts to UTC range: 2024-01-29 17:00:00 to 2024-01-30 16:59:59

        return response()->json($appointments);
    }

    public function show(Appointment $appointment)
    {
        // Dates automatically in workspace timezone
        return response()->json($appointment);
        // Returns: "scheduled_at": "2024-01-30 14:00:00" (Asia/Jakarta)
    }
}
```

### API Request/Response

**Create Appointment:**

```bash
POST /api/appointments
Content-Type: application/json

{
  "patient_id": "01HQXXX",
  "doctor_id": "01HQYYY",
  "scheduled_at": "2024-01-30 14:00:00",
  "notes": "Regular checkup"
}
```

**Response:**

```json
{
  "id": "01HQZZZ",
  "patient_id": "01HQXXX",
  "doctor_id": "01HQYYY",
  "scheduled_at": "2024-01-30 14:00:00",
  "completed_at": null,
  "notes": "Regular checkup",
  "created_at": "2024-01-15 10:30:00",
  "updated_at": "2024-01-15 10:30:00"
}
```

**Database (UTC):**

```
| id      | scheduled_at        | completed_at | created_at          |
|---------|---------------------|--------------|---------------------|
| 01HQZZZ | 2024-01-30 07:00:00 | NULL         | 2024-01-15 03:30:00 |
```

## Common Patterns

### Filtering by Date

```php
// Search endpoint with date filter
public function search(Request $request)
{
    $query = Appointment::query();

    // Automatic timezone conversion with scopeWithParameters
    if ($request->has('search_scheduled_at')) {
        $query->withParameters();
    }

    // Manual filtering also works
    if ($request->has('from_date')) {
        $query->where('scheduled_at', '>=', $request->from_date);
        // Input: "2024-01-30 00:00:00" (Asia/Jakarta)
        // Query: WHERE scheduled_at >= '2024-01-29 17:00:00' (UTC)
    }

    return response()->json($query->get());
}
```

### Date Comparison

```php
// Find upcoming appointments
public function upcoming()
{
    $now = now(); // Already in workspace timezone!

    $appointments = Appointment::where('scheduled_at', '>', $now)
        ->orderBy('scheduled_at')
        ->get();

    return response()->json($appointments);
}
```

### Date Formatting

```php
// Custom date format in resource
class AppointmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'scheduled_at' => $this->scheduled_at, // Already converted to workspace TZ
            'scheduled_date' => $this->scheduled_at->format('Y-m-d'),
            'scheduled_time' => $this->scheduled_at->format('H:i'),
            'scheduled_day' => $this->scheduled_at->format('l'), // Monday, Tuesday, etc.
        ];
    }
}
```

## Different Workspaces Example

### Workspace 1: Jakarta Clinic

```php
$workspace->setting = [
    'timezone' => [
        'name' => 'Asia/Jakarta', // UTC+7
    ]
];
```

**API Response:**
```json
{
  "scheduled_at": "2024-01-30 14:00:00"
}
```

### Workspace 2: Tokyo Clinic

```php
$workspace->setting = [
    'timezone' => [
        'name' => 'Asia/Tokyo', // UTC+9
    ]
];
```

**API Response (same appointment):**
```json
{
  "scheduled_at": "2024-01-30 16:00:00"
}
```

### Workspace 3: London Clinic

```php
$workspace->setting = [
    'timezone' => [
        'name' => 'Europe/London', // UTC+0
    ]
];
```

**API Response (same appointment):**
```json
{
  "scheduled_at": "2024-01-30 07:00:00"
}
```

All three show the same moment in time, just in different timezones!

## Debugging

If dates aren't showing correctly:

```php
// In your controller, check the timezone
dd([
    'workspace_timezone' => config('app.client_timezone'),
    'current_timezone' => date_default_timezone_get(),
    'appointment' => $appointment->scheduled_at,
]);
```

Expected output:

```php
[
  "workspace_timezone" => "Asia/Jakarta",
  "current_timezone" => "Asia/Jakarta",
  "appointment" => Carbon @1706601600 {
    date: 2024-01-30 14:00:00.0 Asia/Jakarta (+07:00)
  }
]
```

## Migration Checklist

- [ ] Register middleware in `bootstrap/app.php`
- [ ] Update model casts to use `TimezonedDateTime::class`
- [ ] Ensure workspace has `setting['timezone']['name']` set
- [ ] Test API requests with date fields
- [ ] Test searching/filtering by date
- [ ] Test with different workspace timezones
- [ ] Update API documentation if needed

## Need Help?

See the full guide: `TIMEZONE_HANDLING_GUIDE.md`
