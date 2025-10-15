<?php

use Illuminate\Support\Facades\Route;
use Projects\WellmedLite\Controllers\API\SatuSehat\{
    Autolist\AutolistController,
    AuthController
};
use Projects\WellmedLite\Controllers\API\SatuSehat\Patient\PatientController;

Route::apiResource('/token',AuthController::class)->only('store');
Route::apiResource('/patient',PatientController::class)->only('store');
Route::apiResource('/autolist/{morph}/{type}',AutolistController::class)->only('index');