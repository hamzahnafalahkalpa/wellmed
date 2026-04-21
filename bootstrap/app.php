<?php

use Hanafalah\LaravelSupport\Response;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Hanafalah\LaravelSupport\Middlewares\LaravelSupportResponse;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;
use App\Http\Middleware\RequestProfiler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up'
        // then: function () {
        //     // Load health routes without any middleware
        //     Route::middleware([])->group(base_path('routes/health.php'));
        // },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->prepend(LaravelSupportResponse::class);
        $middleware->group('universal', []);

        // Add profiler to API middleware group (runs on all API routes)
        $middleware->prependToGroup('api', RequestProfiler::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
        if (app()->isBooted()) {
            (new Response)->exceptionRespond($exceptions);
        }
    })->create();
