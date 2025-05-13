<?php

namespace Projects\Klinik\Providers;

use Exception;
use Illuminate\Foundation\Http\Kernel;
use Hanafalah\LaravelSupport\{
    Concerns\NowYouSeeMe,
    Supports\PathRegistry
};
use Illuminate\Support\Str;
use Hanafalah\ModuleWorkspace\Contracts\Data as WorkspaceDTO;
use Projects\Klinik\{
    Klinik,
    Contracts,
    Facades
};
use Hanafalah\LaravelSupport\Middlewares\PayloadMonitoring;

class KlinikServiceProvider extends KlinikEnvironment
{
    use NowYouSeeMe;

    public function register()
    {
        $this->registerMainClass(Klinik::class)
             ->registerCommandService(CommandServiceProvider::class)
             ->registerServices(function(){
                 $this->binds([
                    Contracts\Klinik::class => function(){
                        return new Klinik;
                    },
                    //WorkspaceDTO\WorkspaceSettingData::class => WorkspaceSettingData::class
                ]);
            });
    }

    public function boot(Kernel $kernel){
        $kernel->pushMiddleware(PayloadMonitoring::class);

        $this->app->booted(function(){
            $model   = Facades\Klinik::myModel($this->TenantModel()->find(Klinik::ID));
            $this->deferredProviders($model);
            
            tenancy()->initialize(Klinik::ID);
            $tenant = tenancy()->tenant;
            $tenant->save();

            $config_name = Str::kebab($model->name); 

            $this->registers([
                '*',
                'Config' => function() {
                    $this->__config_klinik = config('klinik');
                },
                'Provider' => function() use ($model,$config_name){
                    $this->bootedRegisters($model->packages, $config_name, __DIR__.'/../'.$this->__config_klinik['libs']['migration'] ?? 'Migrations');
                    $this->registerOverideConfig($config_name,__DIR__.'/../'.$this->__config_klinik['libs']['config']);
                },
                'Model', 'Database'
            ]);
            $this->registerRouteService(RouteServiceProvider::class);

            $this->app->singleton(PathRegistry::class, function () {
                $registry = new PathRegistry();

                $config = config("klinik");
                foreach ($config['libs'] as $key => $lib) $registry->set($key, 'app/Projects'.$lib);
                return $registry;
            });
        });
    }
}
