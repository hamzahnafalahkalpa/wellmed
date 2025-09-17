<?php

namespace WellmedLite\TenantWellmedLite\Providers;

use Exception;
use Illuminate\Foundation\Http\Kernel;
use Hanafalah\LaravelSupport\{
    Concerns\NowYouSeeMe,
    Supports\PathRegistry
};
use Illuminate\Support\Str;
use WellmedLite\TenantWellmedLite\{
    TenantWellmedLite,
    Contracts,
    Facades
};
use Hanafalah\LaravelSupport\Middlewares\PayloadMonitoring;

class TenantWellmedLiteServiceProvider extends TenantWellmedLiteEnvironment
{
    use NowYouSeeMe;

    public function register()
    {
        $this->registerMainClass(TenantWellmedLite::class)
             ->registerCommandService(CommandServiceProvider::class)
             ->registerServices(function(){
                 $this->binds([
                    Contracts\TenantWellmedLite::class => function(){
                        return new TenantWellmedLite;
                    },
                    //WorkspaceDTO\WorkspaceSettingData::class => WorkspaceSettingData::class
                ]);
            });
    }

    public function boot(Kernel $kernel){
        $kernel->pushMiddleware(PayloadMonitoring::class);
        $this->app->booted(function(){
            // codes that will be run after the package booted
            $model = Facades\TenantWellmedLite::myModel($this->WorkspaceModel()->find(TenantWellmedLite::ID));
            if (isset($model)){
                $config_name = Str::kebab($model->name);

                $this->registers([
                    '*',
                    'Config' => function() {
                        $this->__config_tenant_wellmed_lite = config('tenant-wellmed-lite');
                    },
                    'Provider' => function() use ($model,$config_name){
                        $this->bootedRegisters($model->packages, $config_name, __DIR__.'/../'.$this->__config_tenant_wellmed_lite['libs']['migration'] ?? 'Migrations');
                        $this->registerOverideConfig($config_name,__DIR__.'/../'.$this->__config_tenant_wellmed_lite['libs']['config']);
                    },
                    'Model', 'Database'
                ]);
                $this->registerRouteService(RouteServiceProvider::class);

                $this->app->singleton(PathRegistry::class, function () {
                    $registry = new PathRegistry();

                    $config = config("tenant-wellmed-lite");
                    foreach ($config['libs'] as $key => $lib) $registry->set($key, 'tenants'.$lib);
                    return $registry;
                });
            }
        });
    }
}
