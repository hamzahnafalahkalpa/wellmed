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
        try {
            MicroTenant::accessOnLogin();
        } catch (\Throwable $th) {
            throw $th;
        }
        return $next($request);
    }
}
