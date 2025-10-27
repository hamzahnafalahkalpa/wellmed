<?php

namespace App\Providers;

use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::automaticallyEagerLoadRelationships();
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
