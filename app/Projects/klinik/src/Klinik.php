<?php

namespace Projects\Klinik;

use Illuminate\Database\Eloquent\Model;
use Hanafalah\LaravelSupport\{
    Concerns\Support\HasRepository,
    Supports\PackageManagement,
    Events as SupportEvents
};
use Projects\Klinik\Contracts\Klinik as ContractsKlinik;

class Klinik extends PackageManagement implements ContractsKlinik{
    use Supports\LocalPath,HasRepository;

    const LOWER_CLASS_NAME = "klinik";
    const SERVICE_TYPE     = "tenant";
    const ID               = "1";

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
