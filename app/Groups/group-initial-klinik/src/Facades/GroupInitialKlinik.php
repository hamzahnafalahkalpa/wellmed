<?php

namespace Klinik\GroupInitialKlinik\Facades;

class GroupInitialKlinik extends \Illuminate\Support\Facades\Facade
{
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor()
  {
    return \Klinik\GroupInitialKlinik\Contracts\GroupInitialKlinik::class;
  }
}
