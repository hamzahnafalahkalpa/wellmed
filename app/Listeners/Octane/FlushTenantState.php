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

        // 6. Clear api-helper request-scoped state
        $this->clearApiHelperState();

        // 7. Clear MicroTenant per-request state (keep provider caches for performance)
        $this->clearMicroTenantState();
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

    /**
     * Clear api-helper request-scoped state
     * This prevents API access data from leaking between requests
     */
    protected function clearApiHelperState(): void
    {
        // Clear static properties in api-helper that are request-scoped
        // These use reflection to reset static properties without causing errors

        $classesToFlush = [
            \Hanafalah\ApiHelper\Supports\BaseApiAccess::class,
        ];

        foreach ($classesToFlush as $class) {
            if (class_exists($class)) {
                try {
                    $reflection = new \ReflectionClass($class);
                    $properties = $reflection->getProperties(\ReflectionProperty::IS_STATIC);

                    foreach ($properties as $property) {
                        $property->setAccessible(true);
                        $propertyName = $property->getName();

                        // Reset specific properties to their default values
                        if (in_array($propertyName, ['__api_access', '__generated_token'])) {
                            if ($propertyName === '__generated_token') {
                                $property->setValue(null, ['token' => null, 'expires_at' => null]);
                            } else {
                                $property->setValue(null, null);
                            }
                        }
                    }
                } catch (\ReflectionException $e) {
                    // Class doesn't exist or couldn't be reflected, skip
                }
            }
        }
    }

    /**
     * Clear MicroTenant per-request state
     * Note: We keep $registeredProviders and $loadedAutoloaders cached
     * as they are valid across requests and improve performance
     */
    protected function clearMicroTenantState(): void
    {
        if (class_exists(\Hanafalah\MicroTenant\MicroTenant::class)) {
            // Only clear the per-request tenant state, not the provider caches
            \Hanafalah\MicroTenant\MicroTenant::$microtenant = null;
        }

        // Clear response caches (method and FormRequest caches)
        if (trait_exists(\Hanafalah\LaravelSupport\Concerns\Support\HasResponse::class)) {
            // Use reflection to call the static method
            if (method_exists(\Hanafalah\LaravelSupport\Response::class, 'flushResponseCaches')) {
                \Hanafalah\LaravelSupport\Response::flushResponseCaches();
            }
        }
    }
}
