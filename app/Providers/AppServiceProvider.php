<?php

namespace App\Providers;

use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::automaticallyEagerLoadRelationships();
        Inertia::share('tenant', fn () => session('tenant'));
        if (config('octane') !== null) MicroTenant::accessOnLogin();
        // Force HTTPS hanya di production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
