<?php

namespace Groups\JMT\Providers;

use Illuminate\Support\ServiceProvider;
use Groups\JMT\Commands;

class CommandServiceProvider extends ServiceProvider
{
    protected $__commands = [
        Commands\MigrateCommand::class,
        Commands\SeedCommand::class,
        Commands\InstallMakeCommand::class
    ];

    /**
     * Register the command.
     *
     * @return void
     */
    public function register()
    {
        $this->commands(config('j-m-t.commands', $this->__commands));
    }

    public function provides()
    {
        return $this->__commands;
    }
}
