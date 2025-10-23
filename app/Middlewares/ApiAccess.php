<?php

namespace App\Middlewares;

use Closure;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Support\Facades\Event;

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
        // Event::listen(\Laravel\Octane\Events\RequestReceived::class, function ($event) {
            try {
                // dd();
                MicroTenant::accessOnLogin();
            } catch (\Throwable $th) {
                dd('sini');
            }
        // });
        return $next($request);
    }
}
