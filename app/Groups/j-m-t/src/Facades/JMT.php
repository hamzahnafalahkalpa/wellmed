<?php

namespace Groups\JMT\Facades;

class JMT extends \Illuminate\Support\Facades\Facade
{
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor()
  {
    return \Groups\JMT\Contracts\JMT::class;
  }
}
