# Timezone Handling for JSON Columns (props, prop_activity)

This guide explains how to handle datetime values in JSON columns with automatic timezone conversion.

## Overview

Laravel's cast system doesn't work on JSON subfields, so we use the `HasDateNormalize` trait to handle:
- Datetime in `props` column (e.g., `props->setting->last_login`)
- Activity timestamps in `prop_activity` column (e.g., `prop_activity->adm_visit->adm_start->at`)
- Any other nested datetime values in JSON structures

## How It Works

```
Database (UTC)          →  HasDateNormalize  →  API Response (Workspace TZ)
"2026-01-27 00:27:40"   →  Convert           →  "2026-01-27 07:27:40"
```

## Setup

### 1. Model Configuration

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Hanafalah\LaravelSupport\Casts\TimezonedDateTime;

class Visit extends Model
{
    // Regular datetime fields use TimezonedDateTime cast
    protected $casts = [
        'scheduled_at' => TimezonedDateTime::class,
        'created_at' => TimezonedDateTime::class,
        'updated_at' => TimezonedDateTime::class,

        // JSON columns need special handling - just declare them as array/json
        'props' => 'array',
        'prop_activity' => 'array',
    ];

    /**
     * Define datetime fields inside props for automatic conversion.
     * This tells HasDateNormalize which props fields to convert.
     *
     * @return array
     */
    public function getPropsQuery(): array
    {
        return [
            'setting_last_login' => 'props->setting->last_login',
            'metadata_created' => 'props->metadata->created',
            'scheduled_reminder' => 'props->reminder->scheduled_at',
        ];
    }
}
```

### 2. API Resource

```php
<?php

namespace App\Http\Resources;

use Hanafalah\LaravelSupport\Resources\ApiResource;

class VisitResource extends ApiResource
{
    // That's it! ApiResource automatically calls normalize()
    // which converts all datetime in props and prop_activity
}
```

## Example: props Column

### Database (UTC)

```json
{
  "id": "01HQXXX",
  "props": {
    "setting": {
      "last_login": "2026-01-27 00:27:40",
      "timezone": "Asia/Jakarta"
    },
    "metadata": {
      "created": "2026-01-15 02:30:00",
      "source": "mobile_app"
    }
  }
}
```

### API Response (Asia/Jakarta, UTC+7)

```json
{
  "id": "01HQXXX",
  "props": {
    "setting": {
      "last_login": "2026-01-27 07:27:40",
      "timezone": "Asia/Jakarta"
    },
    "metadata": {
      "created": "2026-01-15 09:30:00",
      "source": "mobile_app"
    }
  }
}
```

## Example: prop_activity Column

### Your Activity Structure

```json
{
  "prop_activity": {
    "adm_visit": {
      "adm_start": {
        "status": 1,
        "message": "Administrasi dibuat",
        "at": "2026-01-27 00:27:40"
      },
      "adm_end": {
        "status": 1,
        "message": "Administrasi selesai",
        "at": "2026-01-27 01:15:00"
      }
    },
    "life_cycle": [
      {
        "adm_start": {
          "status": 1,
          "message": "Administrasi dibuat",
          "at": "2026-01-27 00:27:40"
        }
      },
      {
        "doctor_assigned": {
          "status": 1,
          "message": "Dokter ditugaskan",
          "at": "2026-01-27 00:30:00"
        }
      }
    ]
  }
}
```

### Database Storage (UTC)

```json
{
  "prop_activity": {
    "adm_visit": {
      "adm_start": {
        "status": 1,
        "message": "Administrasi dibuat",
        "at": "2026-01-27 00:27:40"
      }
    },
    "life_cycle": [
      {
        "adm_start": {
          "status": 1,
          "message": "Administrasi dibuat",
          "at": "2026-01-27 00:27:40"
        }
      }
    ]
  }
}
```

### API Response (Asia/Jakarta, UTC+7)

```json
{
  "prop_activity": {
    "adm_visit": {
      "adm_start": {
        "status": 1,
        "message": "Administrasi dibuat",
        "at": "2026-01-27 07:27:40"
      }
    },
    "life_cycle": [
      {
        "adm_start": {
          "status": 1,
          "message": "Administrasi dibuat",
          "at": "2026-01-27 07:27:40"
        }
      }
    ]
  }
}
```

## Creating Records with prop_activity

### Controller Example

```php
<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
        ]);

        // Create visit with initial activity
        $visit = Visit::create([
            'patient_id' => $validated['patient_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'prop_activity' => [
                'adm_visit' => [
                    'adm_start' => [
                        'status' => 1,
                        'message' => 'Administrasi dibuat',
                        'at' => now()->format('Y-m-d H:i:s'), // Will be stored as UTC
                    ]
                ],
                'life_cycle' => [
                    [
                        'adm_start' => [
                            'status' => 1,
                            'message' => 'Administrasi dibuat',
                            'at' => now()->format('Y-m-d H:i:s'), // Will be stored as UTC
                        ]
                    ]
                ]
            ]
        ]);

        return new VisitResource($visit);
        // Returns with all "at" timestamps converted to workspace timezone
    }

    public function updateActivity(Visit $visit, Request $request)
    {
        $activity = $visit->prop_activity;

        // Add new activity
        $activity['adm_visit']['adm_end'] = [
            'status' => 1,
            'message' => 'Administrasi selesai',
            'at' => now()->format('Y-m-d H:i:s'),
        ];

        // Add to life cycle
        $activity['life_cycle'][] = [
            'adm_end' => [
                'status' => 1,
                'message' => 'Administrasi selesai',
                'at' => now()->format('Y-m-d H:i:s'),
            ]
        ];

        $visit->update(['prop_activity' => $activity]);

        return new VisitResource($visit);
    }
}
```

## Activity Helper Trait (Optional)

Create a helper trait for managing activities:

```php
<?php

namespace App\Concerns;

trait HasVisitActivity
{
    /**
     * Add an activity to prop_activity.
     *
     * @param  string  $key  Activity key (e.g., 'adm_start', 'doctor_assigned')
     * @param  int     $status
     * @param  string  $message
     * @return self
     */
    public function addActivity(string $key, int $status, string $message): self
    {
        $activity = $this->prop_activity ?? [];

        // Determine section name from key (e.g., adm_start -> adm_visit)
        $section = explode('_', $key)[0] . '_visit';

        // Add to section
        $activity[$section][$key] = [
            'status' => $status,
            'message' => $message,
            'at' => now()->format('Y-m-d H:i:s'), // Stored as UTC
        ];

        // Add to life cycle
        if (!isset($activity['life_cycle'])) {
            $activity['life_cycle'] = [];
        }

        $activity['life_cycle'][] = [
            $key => [
                'status' => $status,
                'message' => $message,
                'at' => now()->format('Y-m-d H:i:s'), // Stored as UTC
            ]
        ];

        $this->prop_activity = $activity;
        $this->save();

        return $this;
    }

    /**
     * Get the latest activity from life cycle.
     *
     * @return array|null
     */
    public function getLatestActivity(): ?array
    {
        if (!isset($this->prop_activity['life_cycle'])) {
            return null;
        }

        $lifecycle = $this->prop_activity['life_cycle'];
        return end($lifecycle) ?: null;
    }
}
```

### Usage

```php
// In your Visit model
class Visit extends Model
{
    use HasVisitActivity;

    // ... rest of model
}

// In your controller
$visit->addActivity('adm_start', 1, 'Administrasi dibuat');
$visit->addActivity('doctor_assigned', 1, 'Dokter ditugaskan');
$visit->addActivity('adm_end', 1, 'Administrasi selesai');

// Get latest
$latest = $visit->getLatestActivity();
// Returns: ['adm_end' => ['status' => 1, 'message' => '...', 'at' => '2026-01-27 07:27:40']]
```

## Querying by prop_activity Dates

### Using scopeWithParameters

```php
// Search by activity date
GET /api/visits?search_prop_activity=2026-01-27

// Controller
public function index(Request $request)
{
    $visits = Visit::withParameters()->get();
    // Automatically converts date to UTC for querying
    return VisitResource::collection($visits);
}
```

### Manual Query

```php
// Find visits created today (workspace timezone)
$visits = Visit::whereRaw("prop_activity->'adm_visit'->'adm_start'->>'at' >= ?", [
    now()->startOfDay()->utc()->format('Y-m-d H:i:s')
])->get();
```

## Testing

```php
public function test_prop_activity_timezone_conversion()
{
    $workspace = Workspace::factory()->create([
        'setting' => [
            'timezone' => ['name' => 'Asia/Jakarta']
        ]
    ]);

    tenancy()->initialize($workspace->tenant);

    // Create with activity
    $visit = Visit::create([
        'patient_id' => $patient->id,
        'prop_activity' => [
            'adm_visit' => [
                'adm_start' => [
                    'status' => 1,
                    'message' => 'Test',
                    'at' => '2026-01-27 07:27:40' // Asia/Jakarta time
                ]
            ]
        ]
    ]);

    // Database should store in UTC
    $this->assertDatabaseHas('visits', [
        'id' => $visit->id,
    ]);

    $dbValue = DB::table('visits')
        ->where('id', $visit->id)
        ->value('prop_activity');

    $activity = json_decode($dbValue, true);
    $this->assertEquals(
        '2026-01-27 00:27:40', // UTC
        $activity['adm_visit']['adm_start']['at']
    );

    // API should return in workspace timezone
    $resource = new VisitResource($visit);
    $array = $resource->resolve();

    $this->assertEquals(
        '2026-01-27 07:27:40', // Asia/Jakarta
        $array['prop_activity']['adm_visit']['adm_start']['at']
    );
}
```

## Common Patterns

### Activity Timeline

```php
public function getActivityTimeline(): array
{
    if (!isset($this->prop_activity['life_cycle'])) {
        return [];
    }

    $timeline = [];
    foreach ($this->prop_activity['life_cycle'] as $entry) {
        foreach ($entry as $key => $data) {
            $timeline[] = [
                'action' => $key,
                'status' => $data['status'],
                'message' => $data['message'],
                'timestamp' => $data['at'], // Already converted by HasDateNormalize
            ];
        }
    }

    return $timeline;
}
```

### Activity Status Check

```php
public function hasCompletedActivity(string $activityKey): bool
{
    $section = explode('_', $activityKey)[0] . '_visit';

    return isset($this->prop_activity[$section][$activityKey]) &&
           $this->prop_activity[$section][$activityKey]['status'] === 1;
}

// Usage
if ($visit->hasCompletedActivity('adm_end')) {
    // Administration completed
}
```

## Important Notes

1. **Always store in UTC**: When setting `at` values, use `now()->format('Y-m-d H:i:s')` which will be in UTC after middleware processing

2. **Automatic conversion**: `HasDateNormalize` automatically converts all `at` fields in `prop_activity` to workspace timezone when loaded through ApiResource

3. **No manual conversion needed**: Don't manually convert timezones - the system does it automatically

4. **Structure flexibility**: The system handles both:
   - Associative arrays: `adm_visit->adm_start`
   - Indexed arrays: `life_cycle[0]->adm_start`

5. **Backward compatible**: Old code continues to work while you gradually adopt the new pattern

## Troubleshooting

### Activity timestamps not converting

**Check:**
1. ApiResource extends `Hanafalah\LaravelSupport\Resources\ApiResource`
2. Model has `prop_activity` cast as `'array'`
3. Workspace has timezone set
4. Timestamp format is exactly `Y-m-d H:i:s`

**Debug:**
```php
$resource = new VisitResource($visit);
dd([
    'timezone' => config('app.client_timezone'),
    'raw_activity' => $visit->getAttributes()['prop_activity'],
    'normalized' => $resource->resolve()['prop_activity'],
]);
```

### props datetime not converting

**Check:**
1. Model has `getPropsQuery()` method defining the datetime paths
2. Path format is correct: `props->field->subfield`
3. Value in database is valid datetime string

**Debug:**
```php
dd([
    'props_query' => $visit->getPropsQuery(),
    'raw_props' => $visit->getAttributes()['props'],
    'casts' => $visit->getCasts(),
]);
```

## See Also

- `TIMEZONE_HANDLING_GUIDE.md` - Complete timezone guide
- `TIMEZONE_EXAMPLE.md` - Quick start examples
- `HasDateNormalize.php` - Trait source code
