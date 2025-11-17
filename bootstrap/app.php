<?php

use Hanafalah\LaravelSupport\Response;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Hanafalah\LaravelSupport\Middlewares\LaravelSupportResponse;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->prepend(LaravelSupportResponse::class);

        $middleware->group('universal', []);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        if (app()->isBooted()) {
            (new Response)->exceptionRespond($exceptions);
        }
    })->create();
