<?php

use Illuminate\Support\Facades\Route;
use Projects\WellmedLite\Controllers\API\SatuSehat\{
    Autolist\AutolistController,
    AuthController
};
use Projects\WellmedLite\Controllers\API\SatuSehat\{
    Organization\OrganizationController,
    Patient\PatientController
};

Route::apiResource('/token',AuthController::class)->only('store');
Route::apiResource('/patient',PatientController::class)->only('store','update');
Route::apiResource('/organization',OrganizationController::class)->only('store','update');
Route::apiResource('/autolist/{morph}/{type}',AutolistController::class)->only('index');