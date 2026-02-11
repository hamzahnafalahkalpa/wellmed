<?php

namespace App\Middlewares;

use Closure;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Hanafalah\ModuleWorkspace\Facades\Workspace;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class ApiAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        $profiling = env('REQUEST_PROFILE', false);
        $laravelStart = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $middlewareStart = microtime(true);
        $timings = [];

        // Profile bootstrap time (before this middleware)
        $timings['bootstrap'] = round(($middlewareStart - $laravelStart) * 1000, 2);

        try {
            // Profile MicroTenant::accessOnLogin
            $t = microtime(true);
            MicroTenant::accessOnLogin();
            $timings['access_on_login'] = round((microtime(true) - $t) * 1000, 2);

            // Profile tenant/workspace setup
            $t = microtime(true);
            if (isset(tenancy()->tenant)){
                $tenant = tenancy()->tenant;
                $reference = $tenant->reference;
                if (isset($reference)) Workspace::setModelWorkspace($reference);
            }
            $timings['tenant_setup'] = round((microtime(true) - $t) * 1000, 2);
        } catch (\Throwable $th) {
            throw $th;
        }

        // Profile controller + remaining middleware
        $t = microtime(true);
        $response = $next($request);
        $timings['controller_and_response'] = round((microtime(true) - $t) * 1000, 2);

        $timings['total'] = round((microtime(true) - $laravelStart) * 1000, 2);

        if (true) {
            $output = "\n";
            $output .= "╔══════════════════════════════════════════════════════════════╗\n";
            $output .= "║          API ACCESS MIDDLEWARE PROFILING                     ║\n";
            $output .= "╠══════════════════════════════════════════════════════════════╣\n";
            $output .= sprintf("║ %-40s %15s ms ║\n", "Bootstrap (providers + autoload):", $timings['bootstrap']);
            $output .= sprintf("║ %-40s %15s ms ║\n", "MicroTenant::accessOnLogin:", $timings['access_on_login']);
            $output .= sprintf("║ %-40s %15s ms ║\n", "Tenant/Workspace Setup:", $timings['tenant_setup']);
            $output .= sprintf("║ %-40s %15s ms ║\n", "Controller + Response:", $timings['controller_and_response']);
            $output .= "╠══════════════════════════════════════════════════════════════╣\n";
            $output .= sprintf("║ %-40s %15s ms ║\n", "TOTAL FROM LARAVEL_START:", $timings['total']);
            $output .= "╠══════════════════════════════════════════════════════════════╣\n";
            $output .= sprintf("║ Route: %-52s ║\n", substr($request->path(), 0, 52));
            $output .= "╚══════════════════════════════════════════════════════════════╝\n";

            Log::info('[ApiAccessProfile]' . $output);
        }

        return $response;
    }
}
