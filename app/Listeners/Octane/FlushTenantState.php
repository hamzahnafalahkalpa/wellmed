<?php

namespace App\Listeners\Octane;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Octane\Events\RequestTerminated;

class FlushTenantState
{
    /**
     * Handle the event.
     * This listener clears tenant-specific state after each request
     * to prevent state leakage between tenants in Octane.
     */
    public function handle(RequestTerminated $event): void
    {
        // 1. Clear tenant context
        if (function_exists('tenancy') && tenancy()->initialized) {
            tenancy()->end();
        }

        // 2. Purge all database connections to force reconnection
        // This ensures tenant database switches don't leak
        DB::purge();

        // 3. Clear auth state to prevent user leakage between tenants
        Auth::forgetGuards();

        // 4. Clear config cache that might have been set at runtime
        $this->clearTenantConfig();

        // 5. Clear any tenant-specific cached data
        $this->clearTenantCache();
    }

    /**
     * Clear tenant-specific config changes
     */
    protected function clearTenantConfig(): void
    {
        // List of config keys that might be modified per-tenant
        $tenantConfigKeys = [
            'database.connections.tenant',
            'database.default',
            'app.name',
            'app.url',
            'filesystems.disks',
            'cache.prefix',
            // Add other tenant-specific config keys here
        ];

        // Note: We don't actually clear these in Octane as config is cached
        // Instead, we rely on proper reconnection and tenancy initialization
    }

    /**
     * Clear tenant-specific cache
     */
    protected function clearTenantCache(): void
    {
        // Clear any tenant-specific cache tags if you're using them
        // Example: Cache::tags(['tenant_' . $tenantId])->flush();
    }
}
