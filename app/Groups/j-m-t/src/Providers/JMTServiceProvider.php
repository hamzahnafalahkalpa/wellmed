<?php

namespace Groups\JMT\Providers;

use Exception;
use Illuminate\Foundation\Http\Kernel;
use Hanafalah\LaravelSupport\{
    Concerns\NowYouSeeMe,
    Supports\PathRegistry
};
use Illuminate\Support\Str;
use Groups\JMT\{
    JMT,
    Contracts,
    Facades
};
use Hanafalah\LaravelSupport\Middlewares\PayloadMonitoring;

class JMTServiceProvider extends JMTEnvironment
{
    use NowYouSeeMe;

    public function register()
    {
        $this->registerMainClass(JMT::class)
             ->registerCommandService(CommandServiceProvider::class)
             ->registerServices(function(){
                 $this->binds([
                    Contracts\JMT::class => function(){
                        return new JMT;
                    },
                    //WorkspaceDTO\WorkspaceSettingData::class => WorkspaceSettingData::class
                ]);
            });
    }

    public function boot(Kernel $kernel){
        $kernel->pushMiddleware(PayloadMonitoring::class);
        // codes that will be run after the package booted
        $this->registers([
            '*',
            'Config' => function() {
                $this->__config_j_m_t = config('j-m-t');
            },
            'Provider' => function() {
                $model = Facades\JMT::myModel($this->WorkspaceModel()->find(JMT::ID));
                if (!isset($model)) throw new Exception('JMT Model not found');
                $config_name = Str::kebab($model->name);
                $this->bootedRegisters($model->packages, $config_name, __DIR__.'/../'.$this->__config_j_m_t['libs']['migration'] ?? 'Migrations');
                $this->registerOverideConfig($config_name,__DIR__.'/../'.$this->__config_j_m_t['libs']['config']);
            },
            'Model', 'Database'
        ]);
        $this->registerRouteService(RouteServiceProvider::class);

        $this->app->singleton(PathRegistry::class, function () {
            $registry = new PathRegistry();

            $config = config("j-m-t");
            foreach ($config['libs'] as $key => $lib) $registry->set($key, 'D:\WebDev\klinik\app\Groups'.$lib);
            return $registry;
        });
    }
}
