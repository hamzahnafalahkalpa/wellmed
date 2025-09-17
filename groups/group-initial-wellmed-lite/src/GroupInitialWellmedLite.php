<?php

namespace WellmedLite\GroupInitialWellmedLite;

use Illuminate\Database\Eloquent\Model;
use Hanafalah\LaravelSupport\{
    Concerns\Support\HasRepository,
    Supports\PackageManagement,
    Events as SupportEvents
};
use WellmedLite\GroupInitialWellmedLite\Contracts\GroupInitialWellmedLite as ContractsGroupInitialWellmedLite;

class GroupInitialWellmedLite extends PackageManagement implements ContractsGroupInitialWellmedLite{
    use Supports\LocalPath,HasRepository;

    const LOWER_CLASS_NAME = "group_initial_wellmed_lite";
    const SERVICE_TYPE     = "tenant";
    const ID               = "5";

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
