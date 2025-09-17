<?php

namespace WellmedLite\GroupInitialWellmedLite\Providers;

use Exception;
use Illuminate\Foundation\Http\Kernel;
use Hanafalah\LaravelSupport\{
    Concerns\NowYouSeeMe,
    Supports\PathRegistry
};
use Illuminate\Support\Str;
use WellmedLite\GroupInitialWellmedLite\{
    GroupInitialWellmedLite,
    Contracts,
    Facades
};
use Hanafalah\LaravelSupport\Middlewares\PayloadMonitoring;

class GroupInitialWellmedLiteServiceProvider extends GroupInitialWellmedLiteEnvironment
{
    use NowYouSeeMe;

    public function register()
    {
        $this->registerMainClass(GroupInitialWellmedLite::class)
             ->registerCommandService(CommandServiceProvider::class)
             ->registerServices(function(){
                 $this->binds([
                    Contracts\GroupInitialWellmedLite::class => function(){
                        return new GroupInitialWellmedLite;
                    },
                    //WorkspaceDTO\WorkspaceSettingData::class => WorkspaceSettingData::class
                ]);
            });
    }

    public function boot(Kernel $kernel){
        $kernel->pushMiddleware(PayloadMonitoring::class);
        $this->app->booted(function(){
            // codes that will be run after the package booted
            $model = Facades\GroupInitialWellmedLite::myModel($this->WorkspaceModel()->find(GroupInitialWellmedLite::ID));
            if (isset($model)){
                $config_name = Str::kebab($model->name);

                $this->registers([
                    '*',
                    'Config' => function() {
                        $this->__config_group_initial_wellmed_lite = config('group-initial-wellmed-lite');
                    },
                    'Provider' => function() use ($model,$config_name){
                        $this->bootedRegisters($model->packages, $config_name, __DIR__.'/../'.$this->__config_group_initial_wellmed_lite['libs']['migration'] ?? 'Migrations');
                        $this->registerOverideConfig($config_name,__DIR__.'/../'.$this->__config_group_initial_wellmed_lite['libs']['config']);
                    },
                    'Model', 'Database'
                ]);
                $this->registerRouteService(RouteServiceProvider::class);

                $this->app->singleton(PathRegistry::class, function () {
                    $registry = new PathRegistry();

                    $config = config("group-initial-wellmed-lite");
                    foreach ($config['libs'] as $key => $lib) $registry->set($key, 'groups'.$lib);
                    return $registry;
                });
            }
        });
    }
}
