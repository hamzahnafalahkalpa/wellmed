<?php

namespace App\Http\Controllers;

use Hanafalah\LaravelSupport\Controllers\BaseController;

abstract class Controller extends BaseController
{
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) session_start();
    }
    //
}
