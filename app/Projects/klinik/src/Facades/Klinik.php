<?php

namespace Projects\Klinik\Facades;

class Klinik extends \Illuminate\Support\Facades\Facade
{
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor()
  {
    return \Projects\Klinik\Contracts\Klinik::class;
  }
}
