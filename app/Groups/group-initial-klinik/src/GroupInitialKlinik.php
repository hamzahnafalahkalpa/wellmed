<?php

namespace Klinik\GroupInitialKlinik;

use Illuminate\Database\Eloquent\Model;
use Hanafalah\LaravelSupport\{
    Concerns\Support\HasRepository,
    Supports\PackageManagement,
    Events as SupportEvents
};
use Klinik\GroupInitialKlinik\Contracts\GroupInitialKlinik as ContractsGroupInitialKlinik;

class GroupInitialKlinik extends PackageManagement implements ContractsGroupInitialKlinik{
    use Supports\LocalPath,HasRepository;

    const LOWER_CLASS_NAME = "group_initial_klinik";
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
