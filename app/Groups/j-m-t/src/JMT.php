<?php

namespace Groups\JMT;

use Illuminate\Database\Eloquent\Model;
use Hanafalah\LaravelSupport\{
    Concerns\Support\HasRepository,
    Supports\PackageManagement,
    Events as SupportEvents
};
use Groups\JMT\Contracts\JMT as ContractsJMT;

class JMT extends PackageManagement implements ContractsJMT{
    use Supports\LocalPath,HasRepository;

    const LOWER_CLASS_NAME = "j_m_t";
    const SERVICE_TYPE     = "tenant";
    const ID               = "7";

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
