<?php

use Illuminate\Support\Facades\Route;
use Projects\WellmedLite\Controllers\API\SatuSehat\{
    Autolist\AutolistController,
    AuthController
};

Route::apiResource('/token',AuthController::class)->only('store');
Route::apiResource('/autolist/{morph}/{type}',AutolistController::class)->only('index');