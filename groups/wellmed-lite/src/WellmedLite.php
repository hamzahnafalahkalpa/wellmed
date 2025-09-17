<?php

namespace WellmedLite\WellmedLite;

use Illuminate\Database\Eloquent\Model;
use Hanafalah\LaravelSupport\{
    Concerns\Support\HasRepository,
    Supports\PackageManagement,
    Events as SupportEvents
};
use WellmedLite\WellmedLite\Contracts\WellmedLite as ContractsWellmedLite;

class WellmedLite extends PackageManagement implements ContractsWellmedLite{
    use Supports\LocalPath,HasRepository;

    const LOWER_CLASS_NAME = "wellmed_lite";
    const SERVICE_TYPE     = "tenant";
    const ID               = "2";

    public ?Model $model;

    public function events(){
        return [
            SupportEvents\InitializingEvent::class => [
                
            ],
            SupportEvents\EventInitialized::class  => [],
            SupportEvents\EndingEvent::class       => [],
            SupportEvents\EventEnded::class        => [],
            //ADD MORE EVENTS
        ];
    }

    protected function dir(): string{
        return __DIR__;
    }
}
