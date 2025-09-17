<?php

namespace WellmedLite\TenantWellmedLite\Facades;

class TenantWellmedLite extends \Illuminate\Support\Facades\Facade
{
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor()
  {
    return \WellmedLite\TenantWellmedLite\Contracts\TenantWellmedLite::class;
  }
}
