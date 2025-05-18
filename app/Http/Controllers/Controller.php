<?php

namespace App\Http\Controllers;

use Hanafalah\LaravelSupport\Controllers\BaseController;

abstract class Controller extends BaseController
{
    public function __construct()
    {
        session_start();
    }
    //
}
