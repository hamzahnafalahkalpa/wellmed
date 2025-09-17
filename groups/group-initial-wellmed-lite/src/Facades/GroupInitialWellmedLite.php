<?php

namespace WellmedLite\GroupInitialWellmedLite\Facades;

class GroupInitialWellmedLite extends \Illuminate\Support\Facades\Facade
{
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor()
  {
    return \WellmedLite\GroupInitialWellmedLite\Contracts\GroupInitialWellmedLite::class;
  }
}
