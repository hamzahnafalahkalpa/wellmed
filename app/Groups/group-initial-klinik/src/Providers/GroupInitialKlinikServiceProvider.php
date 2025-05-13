<?php

namespace Klinik\GroupInitialKlinik\Providers;

use Exception;
use Illuminate\Foundation\Http\Kernel;
use Hanafalah\LaravelSupport\{
    Concerns\NowYouSeeMe,
    Supports\PathRegistry
};
use Illuminate\Support\Str;
use Klinik\GroupInitialKlinik\{
    GroupInitialKlinik,
    Contracts,
    Facades
};
use Hanafalah\LaravelSupport\Middlewares\PayloadMonitoring;

class GroupInitialKlinikServiceProvider extends GroupInitialKlinikEnvironment
{
    use NowYouSeeMe;

    public function register()
    {
        $this->registerMainClass(GroupInitialKlinik::class)
             ->registerCommandService(CommandServiceProvider::class)
             ->registerServices(function(){
                 $this->binds([
                    Contracts\GroupInitialKlinik::class => function(){
                        return new GroupInitialKlinik;
                    },
                    //WorkspaceDTO\WorkspaceSettingData::class => WorkspaceSettingData::class
                ]);
            });
    }

    public function boot(Kernel $kernel){
        $kernel->pushMiddleware(PayloadMonitoring::class);
        // codes that will be run after the package booted
        $this->app->booted(function(){
            $model   = Facades\GroupInitialKlinik::myModel($this->TenantModel()->find(GroupInitialKlinik::ID));
            $this->deferredProviders($model);

            tenancy()->initialize(GroupInitialKlinik::ID);
            $tenant = tenancy()->tenant;
            $tenant->save();

            $config_name = Str::kebab($model->name); 

            $this->registers([
                '*',
                'Config' => function() {
                    $this->__config_group_initial_klinik = config('group-initial-klinik');
                },
                'Provider' => function() use ($model,$config_name){
                    $this->bootedRegisters($model->packages, $config_name, __DIR__.'/../'.$this->__config_group_initial_klinik['libs']['migration'] ?? 'Migrations');
                    $this->registerOverideConfig($config_name,__DIR__.'/../'.$this->__config_group_initial_klinik['libs']['config']);
                },
                'Model', 'Database'
            ]);
            $this->registerRouteService(RouteServiceProvider::class);

            $this->app->singleton(PathRegistry::class, function () {
                $registry = new PathRegistry();

                $config = config("group-initial-klinik");
                foreach ($config['libs'] as $key => $lib) $registry->set($key, 'app/Groups'.$lib);
                return $registry;
            });
        });
    }
}
