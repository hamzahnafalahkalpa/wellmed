<?php

namespace WellmedLite\TenantWellmedLite;

use Illuminate\Database\Eloquent\Model;
use Hanafalah\LaravelSupport\{
    Concerns\Support\HasRepository,
    Supports\PackageManagement,
    Events as SupportEvents
};
use WellmedLite\TenantWellmedLite\Contracts\TenantWellmedLite as ContractsTenantWellmedLite;

class TenantWellmedLite extends PackageManagement implements ContractsTenantWellmedLite{
    use Supports\LocalPath,HasRepository;

    const LOWER_CLASS_NAME = "tenant_wellmed_lite";
    const SERVICE_TYPE     = "tenant";
    const ID               = "6";

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
