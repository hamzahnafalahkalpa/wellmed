<?php

namespace WellmedLite\WellmedLite\Providers;

use Illuminate\Contracts\Container\Container;
use Hanafalah\LaravelSupport\Providers\BaseServiceProvider;

abstract class WellmedLiteEnvironment extends BaseServiceProvider{
    protected $__config_wellmed_lite = [];
    protected string $__lower_package_name;
    protected string $__config_base_path      = '/../Config';
    protected string $__migration_base_path   = '/../{{database_namespace}}';
    protected string $__migration_target_path = '';

    public function __construct(Container $app) {
        parent::__construct($app);
        $this->__config_wellmed_lite = $this->__config['wellmed-lite'];
    }

    protected function migrationPath(string $path = ''): string{
        return database_path($path);
    }

    protected function dir(): string{
        return __DIR__;
    }

    public function basePath(?string $path = null): string{
        return $this->dir() . '/../'.($path ? $path.'/' : '');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(){
        return [];
    }

    public function registerViews(){
        $viewPath   = resource_path('views/'.$this->__lower_package_name);

        $sourcePath = $this->dir().'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->__lower_package_name . '-views']);

        $this->loadViewsFrom($this->mergeArray($this->getPublishableViewPaths($this->__lower_package_name), [$sourcePath]), $this->__lower_package_name);
    }    
}