<?php

namespace App\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OctaneTenantIsolation
{
    /**
     * Handle an incoming request with proper tenant isolation for Octane.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Store tenant info at the start of request
        $startTenant = tenancy()->tenant?->getTenantKey();
        $startDatabase = DB::connection()->getDatabaseName();

        try {
            $response = $next($request);

            // Verify tenant hasn't leaked
            $this->verifyTenantIsolation($startTenant, $startDatabase);

            return $response;

        } catch (\Throwable $e) {
            // Ensure tenant context is cleaned even on error
            $this->emergencyTenantCleanup();
            throw $e;
        }
    }

    /**
     * Verify tenant isolation hasn't been broken
     */
    protected function verifyTenantIsolation(?string $startTenant, ?string $startDatabase): void
    {
        $endTenant = tenancy()->tenant?->getTenantKey();
        $endDatabase = DB::connection()->getDatabaseName();

        // If tenant changed during request, log warning
        if ($startTenant !== $endTenant) {
            Log::warning('Tenant leaked during request', [
                'start_tenant' => $startTenant,
                'end_tenant' => $endTenant,
                'start_db' => $startDatabase,
                'end_db' => $endDatabase,
            ]);
        }
    }

    /**
     * Emergency cleanup if something goes wrong
     */
    protected function emergencyTenantCleanup(): void
    {
        try {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            DB::purge();
        } catch (\Throwable $e) {
            Log::error('Failed to cleanup tenant state', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Terminate middleware - runs after response is sent
     * Perfect for cleanup without affecting response time
     */
    public function terminate(Request $request, Response $response): void
    {
        // Final cleanup after response is sent to client
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        // Disconnect to ensure clean state for next request
        DB::disconnect();
    }
}
