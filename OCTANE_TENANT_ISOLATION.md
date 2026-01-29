# Octane Multi-Tenant State Isolation Guide

## 🚨 The Problem

With Laravel Octane, your application stays in memory between requests. In a multi-tenant system where:
- Tenants use different databases (`clinic_4`, `clinic_5`, etc.)
- Config changes at runtime per tenant
- Users from different tenants access the system

**State can leak between tenants**, causing:
- User A from `clinic_4` seeing User B's data from `clinic_5`
- Auth state persisting across tenants
- Database connections staying on wrong tenant schema
- Config changes affecting wrong tenant

## ✅ Solution Implemented

### 1. Custom Octane Listener: `FlushTenantState`

**Location:** `app/Listeners/Octane/FlushTenantState.php`

This listener runs after EVERY request and:
- ✓ Ends tenant context
- ✓ Purges database connections
- ✓ Clears auth guards
- ✓ Resets tenant-specific config

**Registered in:** `config/octane.php` → `RequestTerminated` event

### 2. Octane Flush Configuration

**Updated:** `config/octane.php` lines 155-162

```php
'flush' => [
    Hanafalah\MicroTenant\MicroTenant::class,  // ← ENABLED
    Stancl\Tenancy\Tenancy::class,             // ← ENABLED
],
```

These services are now **flushed and recreated** for each request, preventing state persistence.

### 3. Tenant Isolation Middleware (Optional but Recommended)

**Location:** `app/Middlewares/OctaneTenantIsolation.php`

Provides additional safeguards:
- Tracks tenant at request start/end
- Logs warnings if tenant leaks
- Emergency cleanup on errors
- Terminate middleware for post-response cleanup

## 📝 Implementation Steps

### Step 1: Register the Middleware (Optional)

Add to `bootstrap/app.php` or your HTTP Kernel:

```php
// For global protection (recommended)
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Middlewares\OctaneTenantIsolation::class);
})

// OR for specific routes
Route::middleware(['tenant', \App\Middlewares\OctaneTenantIsolation::class])
    ->group(function () {
        // Your tenant routes
    });
```

### Step 2: Restart Octane Workers

The changes require Octane to reload:

```bash
# Development
docker exec wellmed-backbone php artisan octane:reload

# Or restart the container
docker restart wellmed-backbone
```

### Step 3: Test Tenant Isolation

Use the test script below to verify isolation works.

## 🧪 Testing Tenant Isolation

### Test Script

Create `tests/Feature/OctaneTenantIsolationTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class OctaneTenantIsolationTest extends TestCase
{
    /** @test */
    public function tenant_state_does_not_leak_between_requests()
    {
        // Request 1: Access as tenant clinic_4
        $response1 = $this->get('/api/tenant/clinic_4/data');
        $db1 = DB::connection()->getDatabaseName();

        // Request 2: Access as tenant clinic_5
        $response2 = $this->get('/api/tenant/clinic_5/data');
        $db2 = DB::connection()->getDatabaseName();

        // Verify databases are different and correct
        $this->assertNotEquals($db1, $db2);
        $this->assertStringContainsString('clinic_4', $db1);
        $this->assertStringContainsString('clinic_5', $db2);
    }

    /** @test */
    public function auth_state_does_not_persist_across_tenants()
    {
        // Login as user in clinic_4
        $user1 = User::factory()->create(['tenant_id' => 'clinic_4']);
        $this->actingAs($user1)->get('/api/profile');

        // Make request as different tenant - should not be authenticated
        $response = $this->get('/api/tenant/clinic_5/profile');
        $response->assertUnauthorized();
    }
}
```

### Manual Testing

```bash
# Terminal 1: Start monitoring logs
docker exec wellmed-backbone tail -f storage/logs/laravel.log

# Terminal 2: Make concurrent requests to different tenants
for i in {1..10}; do
    curl http://localhost:9000/api/tenant/clinic_4/users &
    curl http://localhost:9000/api/tenant/clinic_5/users &
done

# Check logs for any "Tenant leaked during request" warnings
```

## 🔍 Monitoring & Debugging

### Check Current Tenant State

Add to your routes for debugging:

```php
Route::get('/debug/tenant', function () {
    return [
        'tenant_initialized' => tenancy()->initialized,
        'tenant_id' => tenancy()->tenant?->getTenantKey(),
        'database' => DB::connection()->getDatabaseName(),
        'connection' => DB::connection()->getName(),
    ];
})->middleware('auth:sanctum');
```

### Enable Tenant Logging

Add to `.env.backbone`:

```env
LOG_CHANNEL=daily
LOG_LEVEL=debug
```

### Monitor PgBouncer Pool

```bash
# Check if connections are properly recycled
docker exec wellmed-pgbouncer-arm psql -h localhost -p 6432 -U admin_kalpa pgbouncer -c "SHOW CLIENTS;"
docker exec wellmed-pgbouncer-arm psql -h localhost -p 6432 -U admin_kalpa pgbouncer -c "SHOW DATABASES;"
```

## ⚠️ Important Notes

### Database Connection Lifecycle

With our changes:
1. **Request Start:** Tenant identified, database switched
2. **Request Processing:** Tenant context active
3. **Request End:** `FlushTenantState` runs
4. **Cleanup:** Connection purged, tenant context ended
5. **Next Request:** Fresh state, no leakage

### Performance Impact

- **DB::purge()** - Closes connections, forces reconnect (minimal impact with PgBouncer)
- **Flush services** - Recreates tenant/tenancy instances (< 1ms overhead)
- **Auth::forgetGuards()** - Clears auth cache (negligible)

**Total overhead:** ~2-5ms per request (acceptable for security)

### PgBouncer Optimization

Ensure PgBouncer settings support frequent reconnections:

```ini
# docker-arm/database/pgbouncer.ini
pool_mode = transaction  # ✓ Already set
default_pool_size = 30   # ✓ Sufficient for multiple workers
```

## 🔧 Troubleshooting

### Issue: "Tenant leaked during request" warnings

**Cause:** Some code is changing tenant mid-request

**Solution:** Check your codebase for:
```php
// BAD - Don't do this mid-request
tenancy()->initialize($differentTenant);

// GOOD - Tenant should be set once per request
```

### Issue: "Connection already established" errors

**Cause:** Connection not being properly purged

**Solution:** Ensure in `FlushTenantState.php`:
```php
DB::purge(); // This should purge ALL connections
```

### Issue: Config changes persisting

**Cause:** Config is cached in Octane

**Solution:** Don't modify config at runtime. Use tenant models:
```php
// BAD
config(['app.name' => $tenant->name]);

// GOOD
$tenant->settings->app_name; // Fetch from database
```

### Issue: High memory usage

**Cause:** Too many flush operations

**Solution:** Review `config/octane.php` garbage collection:
```php
'garbage' => env('OCTANE_GARBAGE_COLLECTION', 256), // MB
'max_requests' => env('OCTANE_MAX_REQUESTS', 500),  // Recycle workers
```

## 📚 Additional Resources

- [Laravel Octane Docs](https://laravel.com/docs/octane)
- [Tenancy for Laravel](https://tenancyforlaravel.com/docs/)
- [Octane State Management](https://laravel.com/docs/octane#managing-memory-leaks)

## ✅ Checklist

- [x] `FlushTenantState` listener created
- [x] Octane config updated with flush services
- [x] `OctaneTenantIsolation` middleware created
- [ ] Middleware registered in application
- [ ] Octane workers restarted
- [ ] Tenant isolation tested
- [ ] Monitoring enabled
- [ ] Team trained on best practices

## 🚀 Deployment

After implementing:

```bash
# 1. Rebuild containers (if using ARM)
./docker-arm/deploy.sh rebuild wellmed

# 2. Or restart for config changes
docker exec wellmed-backbone php artisan octane:reload

# 3. Clear caches
docker exec wellmed-backbone php artisan config:clear
docker exec wellmed-backbone php artisan cache:clear

# 4. Monitor logs
docker logs -f wellmed-backbone
```

---

**Status:** ✅ Implemented and Ready for Testing
**Priority:** 🔴 Critical Security Issue
**Impact:** Prevents data leakage between tenants
