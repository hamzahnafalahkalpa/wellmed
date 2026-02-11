<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\RequestProfilerService;

class RequestProfiler
{
    /**
     * Handle an incoming request and profile timing.
     */
    public function handle(Request $request, Closure $next)
    {
        $profiler = RequestProfilerService::getInstance();

        if (!$profiler->isEnabled()) {
            return $next($request);
        }

        // Start query tracking
        $profiler->startQueryTracking();

        // Checkpoint: Middleware started
        $profiler->checkpoint('middleware_start', 'middleware');

        // Start timing sections
        $profiler->start('bootstrap');
        $laravelStart = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $bootstrapTime = round((microtime(true) - $laravelStart) * 1000, 2);
        $profiler->end('bootstrap');

        // Store profiler in request for controller access
        $request->attributes->set('_profiler', $profiler);

        // Checkpoint: Before routing/controller
        $profiler->checkpoint('before_controller', 'routing');
        $profiler->start('controller');

        // Execute the rest of the middleware stack + controller
        $response = $next($request);

        // End controller timing
        $profiler->end('controller');
        $profiler->checkpoint('after_controller', 'routing');

        // Start response processing
        $profiler->start('response_processing');
        $profiler->checkpoint('response_start', 'response');

        // End response processing (minimal since we're about to return)
        $profiler->end('response_processing');
        $profiler->checkpoint('request_complete', 'response');

        // Log the timeline
        $profiler->logTimeline($request->path(), $request->method());

        // Also log formatted output for easy reading
        Log::info('[RequestTimeline]' . $profiler->getFormattedOutput($request->path(), $request->method()));

        return $response;
    }

    /**
     * Handle tasks after the response has been sent.
     */
    public function terminate(Request $request, $response): void
    {
        // Reset profiler for next request (important for Octane)
        RequestProfilerService::reset();
    }
}
