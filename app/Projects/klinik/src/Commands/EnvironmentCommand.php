<?php

namespace Projects\Klinik\Commands;

use Hanafalah\LaravelSupport\Concerns\ServiceProvider\HasMigrationConfiguration;

class EnvironmentCommand extends \Hanafalah\LaravelSupport\Commands\BaseCommand
{
    use HasMigrationConfiguration;
    
    protected function init(): self
    {
        return $this;
    }

    protected function dir(): string
    {
        return __DIR__ . '/../';
    }

}
