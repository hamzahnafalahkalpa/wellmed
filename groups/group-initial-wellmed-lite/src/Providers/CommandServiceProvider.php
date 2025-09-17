<?php

namespace WellmedLite\GroupInitialWellmedLite\Providers;

use Illuminate\Support\ServiceProvider;
use WellmedLite\GroupInitialWellmedLite\Commands;

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
        $this->commands(config('group-initial-wellmed-lite.commands', $this->__commands));
    }

    public function provides()
    {
        return $this->__commands;
    }
}
