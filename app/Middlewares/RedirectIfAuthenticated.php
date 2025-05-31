<?php

namespace App\Middlewares;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated as MiddlewareRedirectIfAuthenticated;
use Illuminate\Support\Facades\Route;

class RedirectIfAuthenticated extends MiddlewareRedirectIfAuthenticated
{
    protected function defaultRedirectUri(): string
    {
        return route('dashboard',['tenant' => 1]);

        $routes = Route::getRoutes()->get('GET');

        foreach (['dashboard', 'home'] as $uri) {
            if (isset($routes[$uri])) {
                return '/'.$uri;
            }
        }

        return '/';
    }
}
