<?php

namespace Projects\Klinik\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Projects\Klinik\Klinik;

class RouteServiceProvider extends ServiceProvider
{
    protected $__lower_package_name;

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @return void
     */
    public function boot()
    {
        $this->__lower_package_name = Klinik::LOWER_CLASS_NAME;
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        if (file_exists(__DIR__.'/../Routes/web.php')){
            Route::group([
                'middleware' => ['web']
            ],function(){
                require __DIR__.'/../Routes/web.php';
            });
        }
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        if (file_exists(__DIR__.'/../Routes/api.php')){
            Route::group([
                'middleware' => ['api'],
                'prefix'     => '/api'
            ],function(){
                require __DIR__.'/../Routes/api.php';
            });
        }
    }
}
