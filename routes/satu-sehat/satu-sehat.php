<?php

use Illuminate\Support\Facades\Route;
use Projects\WellmedLite\Controllers\API\SatuSehat\{
    Autolist\AutolistController,
    AuthController
};
use Projects\WellmedLite\Controllers\API\SatuSehat\{
    Location\LocationController,
    Organization\OrganizationController,
    Patient\PatientController,
    Encounter\EncounterController,
    Observation\ObservationController
};

Route::apiResource('/token',AuthController::class)->only('store');
Route::apiResource('/patient',PatientController::class)->only('store','update');
Route::apiResource('/organization',OrganizationController::class)->only('store','update');
Route::apiResource('/location',LocationController::class)->only('store','update');
Route::apiResource('/encounter',EncounterController::class)->only('store','update');
Route::apiResource('/observation',ObservationController::class)->only('store','update');
Route::apiResource('/autolist/{morph}/{type}',AutolistController::class)->only('index');