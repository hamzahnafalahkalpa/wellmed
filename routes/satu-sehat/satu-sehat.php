<?php

use Illuminate\Support\Facades\Route;
use Projects\WellmedLite\Controllers\API\SatuSehat\{
    AuthController
};

Route::apiResource('/token',AuthController::class)->only('store');